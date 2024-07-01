<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Contract\JobStateChangePayloadContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

abstract class AbstractJobTransitionAction
{
    protected ?SelectQueryBuilder $selectQueryBuilder = null;

    protected readonly QueryFactory $queryFactory;

    /**
     * @return list<string>
     * @throws UnsupportedStorageKeyException
     */
    protected function getJobIds(JobKeyCollection $jobKeys): array
    {
        $jobIds = [];

        foreach ($jobKeys as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($jobKey));
            }

            $jobIds[Id::toBinary($jobKey->getUuid())] = true;
        }

        return \array_keys($jobIds);
    }

    /**
     * @return array{affected: JobKeyCollection, skipped: JobKeyCollection}
     */
    protected function transition(
        JobStateChangePayloadContract $payload,
        Connection $connection,
        string $newState,
        string $selectQueryIdentifier,
    ): array {
        $jobIds = $this->getJobIds($payload->getJobKeys());
        $createdAt = DateTime::toStorage($payload->getCreatedAt());
        $message = $payload->getMessage();
        $transactionId = Id::randomBinary();

        $affected = $this->updateAndCollectNumberAffected($jobIds, $transactionId);

        if ($affected < \count($jobIds)) {
            $affectedJobIds = \iterable_to_array(
                $this->getSelectQueryBuilder($selectQueryIdentifier)
                    ->setParameter('transactionId', $transactionId)
                    ->iterateColumn()
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
                'state_id' => $newState,
                'message' => $message,
                'created_at' => $createdAt,
            ], [
                'id' => Types::BINARY,
                'job_id' => Types::BINARY,
                'state_id' => Types::BINARY,
            ]);
        }

        return [
            'affected' => $this->packJobKeys($jobIds),
            'skipped' => $this->packJobKeys($skippedJobIds),
        ];
    }

    protected function packJobKeys(array $jobIds): JobKeyCollection
    {
        $result = new JobKeyCollection();

        foreach ($jobIds as $jobId) {
            $result->push([new JobStorageKey($jobId)]);
        }

        return $result;
    }

    protected function getSelectQueryBuilder(string $queryIdentifier): SelectQueryBuilder
    {
        if ($this->selectQueryBuilder instanceof SelectQueryBuilder) {
            return $this->selectQueryBuilder;
        }

        $queryBuilder = $this->queryFactory->createSelectBuilder($queryIdentifier);
        $expr = $queryBuilder->expr();

        return $this->selectQueryBuilder = $queryBuilder->select('job.id')
            ->from('heptaconnect_job', 'job')
            ->addOrderBy('job.id')
            ->where($expr->eq('job.transaction_id', ':transactionId'));
    }

    /**
     * @param list<string> $jobIds
     */
    protected abstract function updateAndCollectNumberAffected(array $jobIds, string $transactionId): int;
}
