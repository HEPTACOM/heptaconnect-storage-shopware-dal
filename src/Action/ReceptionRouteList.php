<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Contract\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\ReceptionRouteListResult;
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
        $sourceKey = $criteria->getSource();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $builder = $this->getBuilderCached();

        $builder->setParameter('source_key', Uuid::fromHexToBytes($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());
        $builder->setParameter('cap', 'reception');

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

        // TODO human readable
        return $builder
            ->from('heptaconnect_route', 'r')
            ->innerJoin(
                'r',
                'heptaconnect_entity_type',
                'e',
                $builder->expr()->eq('e.id', 'r.type_id')
            )
            ->innerJoin(
                'r',
                'heptaconnect_route_has_capability',
                'rc',
                $builder->expr()->eq('rc.route_id', 'r.id')
            )
            ->innerJoin(
                'rc',
                'heptaconnect_route_capability',
                'c',
                $builder->expr()->eq('c.id', 'rc.route_capability_id')
            )
            ->select(['r.id id'])
            ->where(
                $builder->expr()->isNull('r.deleted_at'),
                $builder->expr()->eq('r.source_id', ':source_key'),
                $builder->expr()->eq('e.type', ':type'),
                $builder->expr()->eq('c.name', ':cap')
            );
    }
}
