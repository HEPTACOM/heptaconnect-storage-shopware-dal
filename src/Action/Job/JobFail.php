<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Fail\JobFailActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Fail\JobFailPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Fail\JobFailResult;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class JobFail implements JobFailActionInterface
{
    private Connection $connection;

    private ?QueryBuilder $updateQueryBuilder = null;

    private ?QueryBuilder $selectQueryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function fail(JobFailPayload $payload): JobFailResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFailResult {
            $jobIds = $this->getJobIds($payload);
            $createdAt = $payload->getCreatedAt()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
            $message = $payload->getMessage();
            $transactionId = Uuid::uuid4()->getBytes();

            $affected = $this->getUpdateQueryBuilder($connection)
                ->setParameter('jobIds', $jobIds, Connection::PARAM_STR_ARRAY)
                ->setParameter('transactionId', $transactionId, Types::BINARY)
                ->execute();

            if ($affected < \count($jobIds)) {
                $affectedJobIds = $this->getSelectQueryBuilder($connection)
                    ->setParameter('transactionId', $transactionId)
                    ->execute()
                    ->fetchAll(FetchMode::COLUMN) ?: [];

                $skippedJobIds = \array_diff($jobIds, $affectedJobIds);
                $jobIds = $affectedJobIds;
            } else {
                $skippedJobIds = [];
            }

            foreach ($jobIds as $jobId) {
                $connection->insert('heptaconnect_job_history', [
                    'id' => Uuid::uuid4()->getBytes(),
                    'job_id' => $jobId,
                    'state_id' => JobStateEnum::failed(),
                    'message' => $message,
                    'created_at' => $createdAt,
                ], [
                    'id' => Types::BINARY,
                    'job_id' => Types::BINARY,
                    'state_id' => Types::BINARY,
                ]);
            }

            return $this->packResult($jobIds, $skippedJobIds);
        });
    }

    protected function getJobIds(JobFailPayload $payload): array
    {
        $jobIds = [];

        foreach ($payload->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                // TODO use custom exception
                throw new \Exception();
            }

            $jobIds[\hex2bin($jobKey->getUuid())] = true;
        }

        return \array_keys($jobIds);
    }

    protected function getUpdateQueryBuilder(Connection $connection): QueryBuilder
    {
        if ($this->updateQueryBuilder instanceof QueryBuilder) {
            return $this->updateQueryBuilder;
        }

        $builder = $connection->createQueryBuilder();
        $expr = $builder->expr();

        return $this->updateQueryBuilder = $builder->update('heptaconnect_job', 'job')
            ->set('job.state_id', ':newStateId')
            ->set('job.transaction_id', ':transactionId')
            ->andWhere($expr->in('job.id', ':jobIds'))
            ->andWhere($expr->eq('job.state_id', ':oldStateId'))
            ->setParameter('newStateId', JobStateEnum::failed(), Types::BINARY)
            ->setParameter('oldStateId', JobStateEnum::started(), Types::BINARY);
    }

    protected function getSelectQueryBuilder(Connection $connection): QueryBuilder
    {
        if ($this->selectQueryBuilder instanceof QueryBuilder) {
            return $this->selectQueryBuilder;
        }

        $queryBuilder = $connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        return $this->selectQueryBuilder = $queryBuilder->select('job.id')
            ->from('heptaconnect_job', 'job')
            ->where($expr->eq('job.transaction_id', ':transactionId'));
    }

    protected function packResult(array $affectedJobIds, array $skippedJobIds): JobFailResult
    {
        $failedJobs = new JobKeyCollection();

        foreach ($affectedJobIds as $affectedJobId) {
            $failedJobs->push([new JobStorageKey($affectedJobId)]);
        }

        $skippedJobs = new JobKeyCollection();

        foreach ($skippedJobIds as $skippedJobId) {
            $skippedJobs->push([new JobStorageKey($skippedJobId)]);
        }

        return new JobFailResult($failedJobs, $skippedJobs);
    }
}
