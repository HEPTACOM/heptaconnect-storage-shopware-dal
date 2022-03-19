<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Clear\PortalNodeStorageClearCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\DeleteException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class PortalNodeStorageClear implements PortalNodeStorageClearActionInterface
{
    public const CLEAR_QUERY = '1087e0dc-07fe-48d7-903c-9353167c3e89';

    private QueryFactory $queryFactory;

    private Connection $connection;

    public function __construct(QueryFactory $queryFactory, Connection $connection)
    {
        $this->queryFactory = $queryFactory;
        $this->connection = $connection;
    }

    public function clear(PortalNodeStorageClearCriteria $criteria): void
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $deleteBuilder = $this->queryFactory->createBuilder(self::CLEAR_QUERY);
        $deleteBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Type::BINARY);

        try {
            $this->connection->transactional(function () use ($deleteBuilder): void {
                $deleteBuilder->execute();
            });
        } catch (\Throwable $throwable) {
            throw new DeleteException(1646209691, $throwable);
        }
    }
}
