<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

class ReceptionRouteList implements ReceptionRouteListActionInterface
{
    public const LIST_QUERY = 'a2dc9481-5738-448a-9c85-617fec45a00d';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    private QueryIterator $iterator;

    public function __construct(QueryFactory $queryFactory, QueryIterator $iterator)
    {
        $this->queryFactory = $queryFactory;
        $this->iterator = $iterator;
    }

    public function list(ReceptionRouteListCriteria $criteria): iterable
    {
        $sourceKey = $criteria->getSourcePortalNodeKey();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $builder = $this->getBuilderCached();

        $builder->setParameter('source_key', Id::toBinary($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());
        $builder->setParameter('capability', RouteCapability::RECEPTION);

        return \iterable_map(
            Id::toHexIterable($this->iterator->iterateColumn($builder)),
            static fn (string $id) => new ReceptionRouteListResult(new RouteStorageKey($id))
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
        $builder = $this->queryFactory->createBuilder(self::LIST_QUERY);

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
            ->innerJoin(
                'route',
                'heptaconnect_route_has_capability',
                'route_has_capability',
                $builder->expr()->eq('route_has_capability.route_id', 'route.id')
            )
            ->innerJoin(
                'route_has_capability',
                'heptaconnect_route_capability',
                'capability',
                $builder->expr()->andX(
                    $builder->expr()->eq('capability.id', 'route_has_capability.route_capability_id'),
                    $builder->expr()->isNull('capability.deleted_at')
                )
            )
            ->addOrderBy('route.id')
            ->select(['route.id id'])
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->isNull('source_portal_node.deleted_at'),
                $builder->expr()->isNull('target_portal_node.deleted_at'),
                $builder->expr()->eq('route.source_id', ':source_key'),
                $builder->expr()->eq('entity_type.type', ':type'),
                $builder->expr()->eq('capability.name', ':capability')
            );
    }
}
