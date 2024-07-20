<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query;

use Doctrine\DBAL\Query\QueryBuilder;

final readonly class PaginatableQueryBuilder
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        public string $sortedBy,
        public QueryBuilderSortingDirection $direction,
    ) {
    }

    public function createPaginatableQueryBuilder(): QueryBuilder
    {
        return (clone $this->queryBuilder)->addOrderBy($this->sortedBy, match ($this->direction) {
            QueryBuilderSortingDirection::ASCENDING => 'ASC',
            QueryBuilderSortingDirection::DESCENDING => 'DESC',
        });
    }
}
