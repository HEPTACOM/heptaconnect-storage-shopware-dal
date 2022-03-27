<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Schedule\JobSchedulePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Schedule\JobScheduleResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobScheduleActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobSchedule implements JobScheduleActionInterface
{
    public const UPDATE_QUERY = '72372e2f-6e02-470b-89d5-b65ee88024b5';

    public const FIND_QUERY = '87c10b4f-3dcd-460d-ba04-b38acbad6cbe';

    private ?QueryBuilder $updateQueryBuilder = null;

    private ?QueryBuilder $selectQueryBuilder = null;

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
    }

    public function schedule(JobSchedulePayload $payload): JobScheduleResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobScheduleResult {
            $jobIds = $this->getJobIds($payload);
            $createdAt = DateTime::toStorage($payload->getCreatedAt());
            $message = $payload->getMessage();
            $transactionId = Id::randomBinary();

            $affected = $this->getUpdateQueryBuilder()
                ->setParameter('jobIds', $jobIds, Connection::PARAM_STR_ARRAY)
                ->setParameter('transactionId', $transactionId, Types::BINARY)
                ->execute();

            if ($affected < \count($jobIds)) {
                $affectedJobIds = \iterable_to_array(
                    $this->getSelectQueryBuilder()->setParameter('transactionId', $transactionId)->iterateColumn()
                );
                $skippedJobIds = \array_diff($jobIds, $affectedJobIds);
                $jobIds = $affectedJobIds;
            } else {
                $skippedJobIds = [];
            }

            foreach ($jobIds as $jobId) {
                $connection->insert('heptaconnect_job_history', [
                    'id' => Id::randomBinary(),
                    'job_id' => $jobId,
                    'state_id' => JobStateEnum::open(),
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

    protected function getJobIds(JobSchedulePayload $payload): array
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

    protected function getUpdateQueryBuilder(): QueryBuilder
    {
        if ($this->updateQueryBuilder instanceof QueryBuilder) {
            return $this->updateQueryBuilder;
        }

        $builder = $this->queryFactory->createBuilder(self::UPDATE_QUERY);
        $expr = $builder->expr();

        return $this->updateQueryBuilder = $builder->update('heptaconnect_job', 'job')
            ->set('job.state_id', ':newStateId')
            ->set('job.transaction_id', ':transactionId')
            ->andWhere($expr->in('job.id', ':jobIds'))
            ->andWhere($expr->in('job.state_id', ':oldStateIds'))
            ->setParameter('newStateId', JobStateEnum::open(), Types::BINARY)
            ->setParameter('oldStateIds', [
                JobStateEnum::failed(),
                JobStateEnum::finished(),
            ], Connection::PARAM_STR_ARRAY);
    }

    protected function getSelectQueryBuilder(): QueryBuilder
    {
        if ($this->selectQueryBuilder instanceof QueryBuilder) {
            return $this->selectQueryBuilder;
        }

        $queryBuilder = $this->queryFactory->createBuilder(self::FIND_QUERY);
        $expr = $queryBuilder->expr();

        return $this->selectQueryBuilder = $queryBuilder->select('job.id')
            ->from('heptaconnect_job', 'job')
            ->addOrderBy('job.id')
            ->where($expr->eq('job.transaction_id', ':transactionId'));
    }

    protected function packResult(array $affectedJobIds, array $skippedJobIds): JobScheduleResult
    {
        $scheduledJobs = new JobKeyCollection();

        foreach ($affectedJobIds as $affectedJobId) {
            $scheduledJobs->push([new JobStorageKey($affectedJobId)]);
        }

        $skippedJobs = new JobKeyCollection();

        foreach ($skippedJobIds as $skippedJobId) {
            $skippedJobs->push([new JobStorageKey($skippedJobId)]);
        }

        return new JobScheduleResult($scheduledJobs, $skippedJobs);
    }
}
