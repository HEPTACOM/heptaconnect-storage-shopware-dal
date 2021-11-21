<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Driver\ResultStatement;
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
            $statement = $query->execute();

            if (!$statement instanceof ResultStatement) {
                throw new \LogicException('$query->execute() should have returned a ResultStatement', 1637467900);
            }

            $rows = \array_values($statement->fetchAll($fetchMode));
            yield from \is_callable($pack) ? \array_map($pack, $rows) : $rows;

            if ($maxResults === null) {
                break;
            }

            $query->setFirstResult(($query->getFirstResult() ?? 0) + $maxResults);
        } while ($rows !== [] && \count($rows) >= $maxResults);
    }
}
