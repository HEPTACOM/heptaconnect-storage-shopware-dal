<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Get\PortalNodeStorageGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final readonly class PortalNodeStorageGet implements PortalNodeStorageGetActionInterface
{
    public const string FETCH_QUERY = '679d6e76-bb9c-410d-ac22-17c64afcb7cc';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function get(PortalNodeStorageGetCriteria $criteria): iterable
    {
        $portalNodeKey = $criteria->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_debug_type($portalNodeKey));
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
            ->andWhere($fetchBuilder->expr()->in('portal_node_storage.key', ':ids'))
            ->andWhere($fetchBuilder->expr()->eq('portal_node.id', ':portal_node_id'))
            ->andWhere($fetchBuilder->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($fetchBuilder->expr()->or(
                $fetchBuilder->expr()->isNull('expired_at'),
                $fetchBuilder->expr()->gt('expired_at', ':now')
            ))
            ->setParameter('ids', \iterable_to_array($criteria->getStorageKeys()), ArrayParameterType::STRING)
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Types::BINARY)
            ->setParameter('now', DateTime::nowToStorage());

        /** @var array{portal_node_id: string, storage_key: string, storage_value: string, storage_type: string} $row */
        foreach ($fetchBuilder->iterateRows() as $row) {
            yield new PortalNodeStorageGetResult(
                new PortalNodeStorageKey(Id::toHex($row['storage_value'])),
                $row['storage_key'],
                $row['storage_type'],
                $row['storage_value']
            );
        }
    }
}
