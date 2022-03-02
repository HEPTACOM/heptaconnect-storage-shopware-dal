<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Clear\PortalNodeStorageClearCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageClearActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\DeleteException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;

class PortalNodeStorageClear implements PortalNodeStorageClearActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function clear(PortalNodeStorageClearCriteria $criteria): void
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return;
        }

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $deleteBuilder = new QueryBuilder($this->connection);
        $deleteBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->setParameter('portal_node_id', \hex2bin($portalNodeKey->getUuid()), Type::BINARY);

        try {
            $this->connection->transactional(function () use ($deleteBuilder) {
                $deleteBuilder->execute();
            });
        } catch (\Throwable $throwable) {
            throw new DeleteException(1646209691, $throwable);
        }
    }
}
