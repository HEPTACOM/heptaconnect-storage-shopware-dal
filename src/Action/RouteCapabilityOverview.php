<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Defaults;

class RouteCapabilityOverview implements RouteCapabilityOverviewActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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

        yield from \iterable_map(
            $builder->execute()->fetchAll(FetchMode::ASSOCIATIVE),
            static fn (array $row): RouteCapabilityOverviewResult => new RouteCapabilityOverviewResult(
                (string) $row['n'],
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['ct'])
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
        $builder = new QueryBuilder($this->connection);

        return $builder
            ->from('heptaconnect_route_capability', 'capability')
            ->select([
                'capability.name n',
                'capability.created_at ct',
            ])
            ->where($builder->expr()->isNull('capability.deleted_at'));
    }
}
