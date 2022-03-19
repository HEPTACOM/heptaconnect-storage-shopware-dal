<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Delete\PortalNodeStorageDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\DeleteException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Shopware\Core\Defaults;

class PortalNodeStorageDelete implements PortalNodeStorageDeleteActionInterface
{
    public const DELETE_EXPIRED_QUERY = '1972fcfd-5d64-4bce-a6b5-19cb6a8ad671';

    public const DELETE_QUERY = '40e42cd4-4ac3-4304-8cfc-9083d37e81cd';

    private QueryFactory $queryFactory;

    private Connection $connection;

    public function __construct(QueryFactory $queryFactory, Connection $connection)
    {
        $this->queryFactory = $queryFactory;
        $this->connection = $connection;
    }

    public function delete(PortalNodeStorageDeleteCriteria $criteria): void
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $now = new \DateTimeImmutable();
        $deleteExpiredBuilder = $this->queryFactory->createBuilder(self::DELETE_EXPIRED_QUERY);
        $deleteExpiredBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteExpiredBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->andWhere($deleteExpiredBuilder->expr()->isNotNull('expired_at'))
            ->andWhere($deleteExpiredBuilder->expr()->lte('expired_at', ':now'))
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Type::BINARY)
            ->setParameter('now', $now->format(Defaults::STORAGE_DATE_TIME_FORMAT));

        $deleteBuilder = $this->queryFactory->createBuilder(self::DELETE_QUERY);
        $deleteBuilder
            ->delete('heptaconnect_portal_node_storage')
            ->andWhere($deleteBuilder->expr()->eq('portal_node_id', ':portal_node_id'))
            ->andWhere($deleteBuilder->expr()->in('`key`', ':keys'))
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Type::BINARY);

        $idsPayloads = \array_chunk(\iterable_to_array($criteria->getStorageKeys()), 500);

        try {
            $this->connection->transactional(function () use ($idsPayloads, $deleteBuilder, $deleteExpiredBuilder): void {
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
