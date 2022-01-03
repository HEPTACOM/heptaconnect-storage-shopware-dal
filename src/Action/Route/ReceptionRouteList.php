<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class ReceptionRouteList implements ReceptionRouteListActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    private QueryIterator $iterator;

    public function __construct(Connection $connection, QueryIterator $iterator)
    {
        $this->connection = $connection;
        $this->iterator = $iterator;
    }

    public function list(ReceptionRouteListCriteria $criteria): iterable
    {
        $sourceKey = $criteria->getSourcePortalNodeKey();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $builder = $this->getBuilderCached();

        $builder->setParameter('source_key', Uuid::fromHexToBytes($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());
        $builder->setParameter('capability', RouteCapability::RECEPTION);

        $ids = $this->iterator->iterateColumn($builder);
        $hexIds = \iterable_map($ids, [Uuid::class, 'fromBytesToHex']);

        yield from \iterable_map($hexIds, static fn (string $id) => new ReceptionRouteListResult(new RouteStorageKey($id)));
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
                'heptaconnect_route_has_capability',
                'route_has_capability',
                $builder->expr()->eq('route_has_capability.route_id', 'route.id')
            )
            ->innerJoin(
                'route_has_capability',
                'heptaconnect_route_capability',
                'capability',
                $builder->expr()->eq('capability.id', 'route_has_capability.route_capability_id')
            )
            ->select(['route.id id'])
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->eq('route.source_id', ':source_key'),
                $builder->expr()->eq('entity_type.type', ':type'),
                $builder->expr()->eq('capability.name', ':capability')
            );
    }
}
