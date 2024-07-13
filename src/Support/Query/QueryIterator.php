<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder as HeptaconnectQueryBuilder;

class QueryIterator
{
    /**
     * @return iterable<int, array<string, string|null>>
     */
    public function iterate(
        QueryBuilder $query,
        string $sortedBy,
        QueryBuilderSortingDirection $direction = QueryBuilderSortingDirection::ASCENDING,
        int $pageSize = 1000
    ): iterable {
        return $this->iterateSafelyPaginated(
            new PaginatableQueryBuilder($query, $sortedBy, $direction),
            \Closure::fromCallable($this->fetchRows(...)),
            $pageSize,
        );
    }

    /**
     * @return iterable<int, string>
     * @throws \LogicException
     */
    public function iterateColumn(
        QueryBuilder $query,
        string $sortedBy,
        QueryBuilderSortingDirection $direction = QueryBuilderSortingDirection::ASCENDING,
        int $pageSize = 1000
    ): iterable {
        return $this->iterateSafelyPaginated(
            new PaginatableQueryBuilder($query, $sortedBy, $direction),
            function (QueryBuilder $qb): array {
                $result = $qb->executeQuery()->fetchFirstColumn();

                foreach ($result as $cell) {
                    if ($cell === null) {
                        if ($qb instanceof HeptaconnectQueryBuilder) {
                            throw new \LogicException(
                                \sprintf('The queried column in query "%s" is expected to not fetch null values but returned a null value', $qb->identifier),
                                1719685570
                            );
                        } else {
                            throw new \LogicException('The queried column is expected to not fetch null values but returned a null value', 1719685570);
                        }
                    }
                }

                return $result;
            },
            $pageSize
        );
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function fetchRows(QueryBuilder $query): array
    {
        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @return array<string, string|null>|null
     */
    public function fetchRow(QueryBuilder $query): ?array
    {
        $result = $query->executeQuery()->fetchAssociative();

        if (!\is_array($result)) {
            return null;
        }

        return $result;
    }

    public function fetchColumn(QueryBuilder $query): ?string
    {
        $result = $query->executeQuery()->fetchOne();

        if (!\is_string($result)) {
            return null;
        }

        return $result;
    }

    public function fetchSingleValue(QueryBuilder $query): ?string
    {
        $row = $this->fetchSingleRow($query);

        if (\is_array($row)) {
            $result = \current($row);

            if ($result === false) {
                return null;
            }

            return $result;
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

        return match (\count($rows)) {
            0 => null,
            1 => \current($rows),
            default => throw new \LogicException('Too many rows in result for a single value selection', 1645901522),
        };
    }

    /**
     * @template T
     *
     * @param callable(QueryBuilder): array<T> $fetchRow
     *
     * @return iterable<int, T>
     */
    public function iterateSafelyPaginated(PaginatableQueryBuilder $paginatableQuery, callable $fetchRow, int $safeFetchSize): iterable
    {
        if ($safeFetchSize < 1) {
            throw new \LogicException('Safe fetch size is too small', 1645901524);
        }

        $query = $paginatableQuery->createPaginatableQueryBuilder();

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
