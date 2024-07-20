<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;

class QueryFactory
{
    /**
     * @param array<string, int> $fallbackPageSizes
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly QueryIterator $queryIterator,
        private array $fallbackPageSizes,
        private readonly int $fallbackPageSize
    ) {
    }

    public function createBuilder(string $identifier): QueryBuilder
    {
        return new QueryBuilder($this->connection, $identifier);
    }

    public function createSelectBuilder(string $identifier): SelectQueryBuilder
    {
        return new SelectQueryBuilder(
            $this->connection,
            $identifier,
            $this->queryIterator,
            $this->fallbackPageSizes[$identifier] ?? $this->fallbackPageSize
        );
    }
}
