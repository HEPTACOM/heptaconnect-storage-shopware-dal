<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Connection;

final class SelectQueryBuilder extends QueryBuilder
{
    private bool $isForUpdate = false;

    public function __construct(
        Connection $connection,
        string $identifier,
        private readonly QueryIterator $queryIterator,
        private readonly int $paginationPageSize,
    ) {
        parent::__construct($connection, $identifier);
    }

    public function getIsForUpdate(): bool
    {
        return $this->isForUpdate;
    }

    public function setIsForUpdate(bool $isForUpdate): void
    {
        $this->isForUpdate = $isForUpdate;
    }

    #[\Override]
    public function getSQL(): string
    {
        $result = parent::getSQL();

        if ($this->getIsForUpdate()) {
            $result .= ' FOR UPDATE';
        }

        return $result;
    }

    public function fetchSingleValue(): ?string
    {
        return $this->queryIterator->fetchSingleValue($this);
    }

    /**
     * @return array<string, string|null>|null
     */
    public function fetchSingleRow(): ?array
    {
        return $this->queryIterator->fetchSingleRow($this);
    }

    /**
     * @return iterable<int, array<string, string|null>>
     */
    public function iterateRows(): iterable
    {
        return $this->queryIterator->iterate($this, $this->paginationPageSize);
    }

    /**
     * @return iterable<int, string|null>
     */
    public function iterateColumn(): iterable
    {
        return $this->queryIterator->iterateColumn($this, $this->paginationPageSize);
    }

    #[\Override]
    public function delete($delete = null, $alias = null): never
    {
        throw new \DomainException(
            \sprintf(
                'Changing an instance of "%s" in query "%s" to perform DELETE statements if prohibited. Use "%s" instead for it',
                SelectQueryBuilder::class,
                $this->identifier,
                QueryBuilder::class,
            ),
            1719673190
        );
    }

    #[\Override]
    public function update($update = null, $alias = null): never
    {
        throw new \DomainException(
            \sprintf(
                'Changing an instance of "%s" in query "%s" to perform UPDATE statements if prohibited. Use "%s" instead for it',
                SelectQueryBuilder::class,
                $this->identifier,
                QueryBuilder::class,
            ),
            1719673191
        );
    }

    #[\Override]
    public function insert($insert = null): never
    {
        throw new \DomainException(
            \sprintf(
                'Changing an instance of "%s" in query "%s" to perform INSERT statements if prohibited. Use "%s" instead for it',
                SelectQueryBuilder::class,
                $this->identifier,
                QueryBuilder::class,
            ),
            1719673192
        );
    }
}
