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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class ReceptionRouteList implements ReceptionRouteListActionInterface
{
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

        // TODO cache built query
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->from('heptaconnect_route', 'r')
            ->innerJoin(
                'r',
                'heptaconnect_entity_type',
                'e',
                $builder->expr()->eq('e.id', 'r.type_id')
            )
            ->select(['r.id id'])
            ->where(
                $builder->expr()->isNull('r.deleted_at'),
                $builder->expr()->eq('r.source_id', ':source_key'),
                $builder->expr()->eq('e.type', ':type'),
            );

        $builder->setParameter('source_key', Uuid::fromHexToBytes($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());

        $ids = $this->iterator->iterateColumn($builder);
        $hexIds = \iterable_map($ids, [Uuid::class, 'fromBytesToHex']);

        yield from \iterable_map($hexIds, static fn (string $id) => new ReceptionRouteListResult(new RouteStorageKey($id)));
    }
}
