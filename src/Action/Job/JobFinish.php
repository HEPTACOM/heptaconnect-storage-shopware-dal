<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobFinish implements JobFinishActionInterface
{
    public const UPDATE_QUERY = '393a0ae1-5f42-4a49-96a3-9a23c26e6bd2';

    public const FIND_QUERY = '84e5495d-4733-4e8a-b775-aafba23daa8c';

    private ?QueryBuilder $updateQueryBuilder = null;

    private ?QueryBuilder $selectQueryBuilder = null;

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory
    ) {
    }

    public function finish(JobFinishPayload $payload): JobFinishResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFinishResult {
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

    private function getJobIds(JobFinishPayload $payload): array
    {
        $jobIds = [];

        foreach ($payload->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException($jobKey::class);
            }

            $jobIds[Id::toBinary($jobKey->getUuid())] = true;
        }

        return \array_keys($jobIds);
    }

    private function getUpdateQueryBuilder(): QueryBuilder
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
            ->andWhere($expr->eq('job.state_id', ':oldStateId'))
            ->setParameter('newStateId', JobStateEnum::finished(), Types::BINARY)
            ->setParameter('oldStateId', JobStateEnum::started(), Types::BINARY);
    }

    private function getSelectQueryBuilder(): QueryBuilder
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

    private function packResult(array $affectedJobIds, array $skippedJobIds): JobFinishResult
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
