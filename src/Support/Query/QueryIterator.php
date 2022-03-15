<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;

class QueryIterator
{
    /**
     * @return iterable<int, array<string, string|null>>
     */
    public function iterate(QueryBuilder $query, int $pageSize = 1000): iterable
    {
        return $this->iterateSafelyPaginated(
            $query,
            \Closure::fromCallable([$this, 'fetchRows']),
            $pageSize,
        );
    }

    /**
     * @return iterable<int, string|null>
     */
    public function iterateColumn(QueryBuilder $query, int $pageSize = 1000): iterable
    {
        return $this->iterateSafelyPaginated(
            $query,
            fn (QueryBuilder $qb): array => $this->getExecuteStatement($qb)->fetchAll(\PDO::FETCH_COLUMN),
            $pageSize
        );
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function fetchRows(QueryBuilder $query): array
    {
        return $this->getExecuteStatement($query)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @return array<string, string|null>|null
     */
    public function fetchRow(QueryBuilder $query): ?array
    {
        return $this->getExecuteStatement($query)->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function fetchColumn(QueryBuilder $query): ?string
    {
        return $this->getExecuteStatement($query)->fetchColumn() ?: null;
    }

    public function fetchSingleValue(QueryBuilder $query): ?string
    {
        $row = $this->fetchSingleRow($query);

        if (\is_array($row)) {
            return \current($row);
        }

        return null;
    }

    /**
     * @return array<string, string|null>|null
     */
    public function fetchSingleRow(QueryBuilder $query): ?array
    {
        $oldLimit = $query->getMaxResults();
        $oldOffset = $query->getFirstResult();

        $query->setFirstResult(0);
        $query->setMaxResults(2);

        try {
            $rows = $this->fetchRows($query);
        } finally {
            $query->setMaxResults($oldLimit);
            $query->setFirstResult($oldOffset);
        }

        switch (\count($rows)) {
            case 0:
                return null;
            case 1:
                return \current($rows);
            default:
                throw new \LogicException('Too many rows in result for a single value selection', 1645901522);
        }
    }

    /**
     * @template T
     *
     * @param callable(QueryBuilder): array<T> $fetchRow
     *
     * @return iterable<int, T>
     */
    public function iterateSafelyPaginated(QueryBuilder $query, callable $fetchRow, int $safeFetchSize): iterable
    {
        if ($safeFetchSize < 1) {
            throw new \LogicException('Safe fetch size is too small', 1645901524);
        }

        if ($query->getQueryPart('orderBy') === []) {
            throw new \LogicException('Pagination without order is not reliable', 1645901525);
        }

        $initOffset = $query->getFirstResult();
        $initLimit = $query->getMaxResults();
        $rowIndexer = $this->createRowIndexer();

        if ($initLimit === null) {
            $query->setMaxResults($safeFetchSize);

            do {
                $rows = $fetchRow($query);
                yield from $rowIndexer($rows);

                $query->setFirstResult($query->getFirstResult() + $safeFetchSize);
            } while ($rows !== [] && \count($rows) >= $safeFetchSize);
        } else {
            $pageSize = \min($initLimit, $safeFetchSize);
            $rowsLeft = $initLimit;

            $query->setMaxResults($pageSize);

            do {
                $rows = $fetchRow($query);
                $rowCount = \count($rows);
                $rowsLeft -= $rowCount;
                yield from $rowIndexer($rows);

                $query->setFirstResult($query->getFirstResult() + $rowCount);
                $query->setMaxResults(\min($rowCount, $rowsLeft));
            } while ($rows !== [] && $rowCount >= $safeFetchSize && $rowsLeft > 0);
        }

        $query->setFirstResult($initOffset);
        $query->setMaxResults($initLimit);
    }

    private function getExecuteStatement(QueryBuilder $query): ResultStatement
    {
        $statement = $query->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('query->execute() should have returned a ResultStatement', 1637467900);
        }

        return $statement;
    }

    /**
     * @return callable(array): iterable<int, array>
     */
    private function createRowIndexer(): callable
    {
        $rowId = 0;

        return static function (array $rows) use (&$rowId): iterable {
            foreach ($rows as $row) {
                yield $rowId++ => $row;
            }
        };
    }
}
