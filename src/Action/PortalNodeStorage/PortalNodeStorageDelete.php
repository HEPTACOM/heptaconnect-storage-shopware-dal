<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Delete\PortalNodeStorageDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\DeleteException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Defaults;

class PortalNodeStorageDelete implements PortalNodeStorageDeleteActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function delete(PortalNodeStorageDeleteCriteria $criteria): void
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return;
        }

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $now = new \DateTimeImmutable();
        $deleteExpiredBuilder = new QueryBuilder($this->connection);
        $deleteExpiredBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteExpiredBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->andWhere($deleteExpiredBuilder->expr()->isNotNull('expired_at'))
            ->andWhere($deleteExpiredBuilder->expr()->lt('expired_at', ':now'))
            ->setParameter('portal_node_id', \hex2bin($portalNodeKey->getUuid()), Type::BINARY)
            ->setParameter('now', $now->format(Defaults::STORAGE_DATE_TIME_FORMAT));

        $deleteBuilder = new QueryBuilder($this->connection);
        $deleteBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->andWhere($deleteBuilder->expr()->in('key', ':keys'))
            ->setParameter('portal_node_id', \hex2bin($portalNodeKey->getUuid()), Type::BINARY);

        $idsPayloads = \array_chunk(\iterable_to_array($criteria->getStorageKeys()), 50);

        try {
            $this->connection->transactional(function () use ($idsPayloads, $deleteBuilder, $deleteExpiredBuilder) {
                $deleteExpiredBuilder->execute();

                foreach ($idsPayloads as $idsPayload) {
                    $deleteBuilder->setParameter('keys', $idsPayload, Connection::PARAM_STR_ARRAY);
                    $deleteBuilder->execute();
                }
            });
        } catch (\Throwable $throwable) {
            throw new DeleteException(1646209690, $throwable);
        }
    }
}
