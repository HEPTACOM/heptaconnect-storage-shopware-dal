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
        private Connection $connection,
        private QueryIterator $queryIterator,
        private array $fallbackPageSizes,
        private int $fallbackPageSize
    ) {
    }

    public function createBuilder(string $identifier): QueryBuilder
    {
        return new QueryBuilder(
            $this->connection,
            $this->queryIterator,
            $identifier,
            $this->fallbackPageSizes[$identifier] ?? $this->fallbackPageSize
        );
    }
}
