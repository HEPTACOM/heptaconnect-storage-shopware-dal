<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteGet implements RouteGetActionInterface
{
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

        if ($ids === []) {
            return [];
        }

        // TODO cache built query
        $builder = $this->getBuilder();
        $builder->setParameter('ids', Uuid::fromHexToBytesList($ids), Connection::PARAM_STR_ARRAY);

        yield from $this->iterator->iterate($builder, static fn (array $row): RouteGetResult => new RouteGetResult(
            new RouteStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
            new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['s_id'])),
            new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['t_id'])),
            (string) $row['e_t']
        ));
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();

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
                'heptaconnect_portal_node',
                's',
                $builder->expr()->eq('s.id', 'r.source_id')
            )
            ->innerJoin(
                'r',
                'heptaconnect_portal_node',
                't',
                $builder->expr()->eq('t.id', 'r.target_id')
            )
            ->select([
                'r.id id',
                'e.type e_t',
                's.id s_id',
                't.id t_id',
            ])
            ->where(
                $builder->expr()->isNull('r.deleted_at'),
                $builder->expr()->in('r.id', ':ids')
            );
    }
}
