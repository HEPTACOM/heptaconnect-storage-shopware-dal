<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteGet implements RouteGetActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    private QueryIterator $iterator;

    public function __construct(Connection $connection, QueryIterator $iterator)
    {
        $this->connection = $connection;
        $this->iterator = $iterator;
    }

    public function get(RouteGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getRoutes() as $routeKey) {
            if (!$routeKey instanceof RouteStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($routeKey));
            }

            $ids[] = $routeKey->getUuid();
        }

        return $ids === [] ? [] : $this->yieldRoutes($ids);
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
                'target_portal_node.id target_portal_node_id',
                'GROUP_CONCAT(capability.name SEPARATOR \',\') capability_name',
            ])
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->in('route.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<RouteGetResult>
     */
    protected function yieldRoutes(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Uuid::fromHexToBytesList($ids), Connection::PARAM_STR_ARRAY);

        yield from $this->iterator->iterate($builder, static fn (array $row): RouteGetResult => new RouteGetResult(
            new RouteStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
            new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['source_portal_node_id'])),
            new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['target_portal_node_id'])),
            (string) $row['entity_type_name'],
            \explode(',', (string) $row['capability_name'])
        ));
    }
}
