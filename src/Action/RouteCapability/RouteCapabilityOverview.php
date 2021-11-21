<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\Overview\RouteCapabilityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\Overview\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\Overview\RouteCapabilityOverviewResult;
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

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1637467903);
        }

        yield from \iterable_map(
            $statement->fetchAll(FetchMode::ASSOCIATIVE),
            static fn (array $row): RouteCapabilityOverviewResult => new RouteCapabilityOverviewResult(
                (string) $row['name'],
                /* @phpstan-ignore-next-line */
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['created_at'])
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
                'capability.name name',
                'capability.created_at created_at',
            ])
            ->where($builder->expr()->isNull('capability.deleted_at'));
    }
}