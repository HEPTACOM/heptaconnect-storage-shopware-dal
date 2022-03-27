<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Listing\PortalNodeStorageListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Listing\PortalNodeStorageListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeStorageList implements PortalNodeStorageListActionInterface
{
    public const FETCH_QUERY = '7e532256-22d2-492e-8e76-ab1649ddc4e0';

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function list(PortalNodeStorageListCriteria $criteria): iterable
    {
        $portalNodeKey = $criteria->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $fetchBuilder = $this->queryFactory->createBuilder(self::FETCH_QUERY);
        $fetchBuilder
            ->from('heptaconnect_portal_node_storage', 'portal_node_storage')
            ->select([
                'portal_node.id portal_node_id',
                'portal_node_storage.key storage_key',
                'portal_node_storage.value storage_value',
                'portal_node_storage.type storage_type',
            ])
            ->innerJoin(
                'portal_node_storage',
                'heptaconnect_portal_node',
                'portal_node',
                $fetchBuilder->expr()->eq('portal_node_storage.portal_node_id', 'portal_node.id')
            )
            ->addOrderBy('portal_node_storage.id')
            ->andWhere($fetchBuilder->expr()->eq('portal_node.id', ':portal_node_id'))
            ->andWhere($fetchBuilder->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($fetchBuilder->expr()->or(
                $fetchBuilder->expr()->isNull('expired_at'),
                $fetchBuilder->expr()->gt('expired_at', ':now')
            ))
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Types::BINARY)
            ->setParameter('now', DateTime::nowToStorage());

        return \iterable_map(
            $fetchBuilder->iterateRows(),
            static fn (array $row): PortalNodeStorageListResult => new PortalNodeStorageListResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['storage_value'])),
                (string) $row['storage_key'],
                (string) $row['storage_type'],
                (string) $row['storage_value']
            )
        );
    }
}
