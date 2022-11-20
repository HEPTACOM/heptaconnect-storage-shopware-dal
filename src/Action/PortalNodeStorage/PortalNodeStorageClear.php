<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Clear\PortalNodeStorageClearCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\DeleteException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeStorageClear implements PortalNodeStorageClearActionInterface
{
    public const CLEAR_QUERY = '1087e0dc-07fe-48d7-903c-9353167c3e89';

    public function __construct(private QueryFactory $queryFactory, private Connection $connection)
    {
    }

    public function clear(PortalNodeStorageClearCriteria $criteria): void
    {
        $portalNodeKey = $criteria->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($portalNodeKey::class);
        }

        $deleteBuilder = $this->queryFactory->createBuilder(self::CLEAR_QUERY);
        $deleteBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Types::BINARY);

        try {
            $this->connection->transactional(function () use ($deleteBuilder): void {
                $deleteBuilder->execute();
            });
        } catch (\Throwable $throwable) {
            throw new DeleteException(1646209691, $throwable);
        }
    }
}
