<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;

class QueryIterator
{
    public function iterate(QueryBuilder $query, callable $pack, int $pageSize = 1000): iterable
    {
        return $this->doIterate($query->setMaxResults($pageSize), FetchMode::ASSOCIATIVE, $pack);
    }

    public function iterateColumn(QueryBuilder $query, int $pageSize = 1000): iterable
    {
        return $this->doIterate($query->setMaxResults($pageSize), FetchMode::COLUMN);
    }

    protected function doIterate(QueryBuilder $query, int $fetchMode, ?callable $pack = null): iterable
    {
        $maxResults = $query->getMaxResults();

        do {
            $rows = $query->execute()->fetchAll($fetchMode) ?: [];

            if (\is_array($rows)) {
                $rows = \array_values($rows);

                yield from \is_callable($pack) ? \array_map($pack, $rows) : $rows;
            }

            $query->setFirstResult(($query->getFirstResult() ?? 0) + $maxResults);
        } while ($rows !== [] && \count($rows) >= $maxResults);
    }
}
