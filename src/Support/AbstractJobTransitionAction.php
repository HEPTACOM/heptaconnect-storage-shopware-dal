<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support;

use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

abstract class AbstractJobTransitionAction
{
    protected ?QueryBuilder $selectQueryBuilder = null;

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

    protected function packJobKeys(array $jobIds): JobKeyCollection
    {
        $result = new JobKeyCollection();

        foreach ($jobIds as $jobId) {
            $result->push([new JobStorageKey($jobId)]);
        }

        return $result;
    }

    protected function getSelectQueryBuilder(string $queryIdentifier): QueryBuilder
    {
        if ($this->selectQueryBuilder instanceof QueryBuilder) {
            return $this->selectQueryBuilder;
        }

        $queryBuilder = $this->queryFactory->createBuilder($queryIdentifier);
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
