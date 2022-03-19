<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability;

use Heptacom\HeptaConnect\Storage\Base\Action\RouteCapability\Overview\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\RouteCapability\Overview\RouteCapabilityOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\RouteCapabilityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class RouteCapabilityOverview implements RouteCapabilityOverviewActionInterface
{
    public const OVERVIEW_QUERY = '329b4aa3-e576-4930-b89f-c63dca05c16e';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function overview(RouteCapabilityOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === RouteCapabilityOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case RouteCapabilityOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'capability.created_at';

                    break;
                case RouteCapabilityOverviewCriteria::FIELD_NAME:
                    $dbalFieldName = 'capability.name';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1636505519);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('capability.id', 'ASC');

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult($page * $pageSize);
            }
        }

        return \iterable_map(
            $builder->iterateRows(),
            static fn (array $row): RouteCapabilityOverviewResult => new RouteCapabilityOverviewResult(
                (string) $row['name'],
                /* @phpstan-ignore-next-line */
                DateTime::fromStorage((string) $row['created_at'])
            )
        );
    }

    protected function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::OVERVIEW_QUERY);

        return $builder
            ->from('heptaconnect_route_capability', 'capability')
            ->select([
                'capability.name name',
                'capability.created_at created_at',
            ])
            ->where($builder->expr()->isNull('capability.deleted_at'));
    }
}
