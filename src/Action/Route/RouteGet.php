<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

class RouteGet implements RouteGetActionInterface
{
    public const FETCH_QUERY = '24ab04cd-03f5-40c8-af25-715856281314';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    private QueryIterator $iterator;

    public function __construct(QueryFactory $queryFactory, QueryIterator $iterator)
    {
        $this->queryFactory = $queryFactory;
        $this->iterator = $iterator;
    }

    public function get(RouteGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getRouteKeys() as $routeKey) {
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
        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);

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
            ->addGroupBy([
                'route.id',
                'entity_type.type',
                'source_portal_node.id',
                'target_portal_node.id',
            ])
            ->addOrderBy('route.id')
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->in('route.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<\Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult>
     */
    protected function yieldRoutes(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Id::toBinaryList($ids), Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $this->iterator->iterate($builder),
            static fn (array $row): RouteGetResult => new RouteGetResult(
                new RouteStorageKey(Id::toHex((string) $row['id'])),
                new PortalNodeStorageKey(Id::toHex((string) $row['source_portal_node_id'])),
                new PortalNodeStorageKey(Id::toHex((string) $row['target_portal_node_id'])),
                /* @phpstan-ignore-next-line */
                (string) $row['entity_type_name'],
                \explode(',', (string) $row['capability_name'])
            )
        );
    }
}
