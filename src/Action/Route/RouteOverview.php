<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Overview\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Overview\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Overview\RouteOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteOverview implements RouteOverviewActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(RouteOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === RouteOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case RouteOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'route.created_at';
                    break;
                case RouteOverviewCriteria::FIELD_ENTITY_TYPE:
                    $dbalFieldName = 'entity_type.type';
                    break;
                case RouteOverviewCriteria::FIELD_SOURCE:
                    // TODO allow sort by portal name
                    $dbalFieldName = 'source_portal_node.class_name';
                    break;
                case RouteOverviewCriteria::FIELD_TARGET:
                    // TODO allow sort by portal name
                    $dbalFieldName = 'target_portal_node.class_name';
                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1636528918);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('route.id', 'ASC');

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
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1637467905);
        }

        yield from \iterable_map(
            $statement->fetchAll(FetchMode::ASSOCIATIVE),
            static fn (array $row): RouteOverviewResult => new RouteOverviewResult(
                new RouteStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
                (string) $row['entity_type_name'],
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['source_portal_node_id'])),
                (string) $row['source_portal_node_class'],
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['target_portal_node_id'])),
                (string) $row['target_portal_node_class'],
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['ct']),
                \explode(',', (string) $row['capability_name'])
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
            ->from('heptaconnect_route', 'route')
            ->innerJoin(
                'route',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('entity_type.id', 'route.type_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'source_portal_node',
                $builder->expr()->eq('source_portal_node.id', 'route.source_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'target_portal_node',
                $builder->expr()->eq('target_portal_node.id', 'route.target_id')
            )
            ->leftJoin(
                'route',
                'heptaconnect_route_has_capability',
                'route_has_capability',
                $builder->expr()->eq('route_has_capability.route_id', 'route.id')
            )
            ->leftJoin(
                'route_has_capability',
                'heptaconnect_route_capability',
                'capability',
                $builder->expr()->eq('route_has_capability.route_capability_id', 'capability.id')
            )
            ->select([
                'route.id id',
                'entity_type.type entity_type_name',
                'source_portal_node.id source_portal_node_id',
                'source_portal_node.class_name source_portal_node_class',
                'target_portal_node.id target_portal_node_id',
                'target_portal_node.class_name target_portal_node_class',
                'route.created_at ct',
                'GROUP_CONCAT(capability.name SEPARATOR \',\') capability_name',
            ])
            ->groupBy([
                'route.id',
                'entity_type.type',
                'source_portal_node.id',
                'source_portal_node.class_name',
                'target_portal_node.id',
                'target_portal_node.class_name',
                'route.created_at',
            ])
            ->where($builder->expr()->isNull('route.deleted_at'));
    }
}
