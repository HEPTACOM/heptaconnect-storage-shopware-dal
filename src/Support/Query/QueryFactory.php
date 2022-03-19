<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;

class QueryFactory
{
    private Connection $connection;

    private QueryIterator $queryIterator;

    /**
     * @var array<string, int>
     */
    private array $fallbackPageSizes;

    private int $fallbackPageSize;

    /**
     * @param array<string, int> $fallbackPageSizes
     */
    public function __construct(
        Connection $connection,
        QueryIterator $queryIterator,
        array $fallbackPageSizes,
        int $fallbackPageSize
    ) {
        $this->connection = $connection;
        $this->queryIterator = $queryIterator;
        $this->fallbackPageSizes = $fallbackPageSizes;
        $this->fallbackPageSize = $fallbackPageSize;
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