<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

class JobFinish implements JobFinishActionInterface
{
    private Connection $connection;

    private ?QueryBuilder $updateQueryBuilder = null;

    private ?QueryBuilder $selectQueryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function finish(JobFinishPayload $payload): JobFinishResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFinishResult {
            $jobIds = $this->getJobIds($payload);
            $createdAt = DateTime::toStorage($payload->getCreatedAt());
            $message = $payload->getMessage();
            $transactionId = Id::randomBinary();

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
                    'id' => Id::randomBinary(),
                    'job_id' => $jobId,
                    'state_id' => JobStateEnum::finished(),
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

    protected function getJobIds(JobFinishPayload $payload): array
    {
        $jobIds = [];

        foreach ($payload->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($jobKey));
            }

            $jobIds[Id::toBinary($jobKey->getUuid())] = true;
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
            ->setParameter('newStateId', JobStateEnum::finished(), Types::BINARY)
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

    protected function packResult(array $affectedJobIds, array $skippedJobIds): JobFinishResult
    {
        $finishedJobs = new JobKeyCollection();

        foreach ($affectedJobIds as $affectedJobId) {
            $finishedJobs->push([new JobStorageKey($affectedJobId)]);
        }

        $skippedJobs = new JobKeyCollection();

        foreach ($skippedJobIds as $skippedJobId) {
            $skippedJobs->push([new JobStorageKey($skippedJobId)]);
        }

        return new JobFinishResult($finishedJobs, $skippedJobs);
    }
}
