<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteFindByTargetsAndType implements RouteFindByTargetsAndTypeActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function find(RouteFindByTargetsAndTypeCriteria $criteria): ?RouteFindByTargetsAndTypeResult
    {
        $sourceKey = $criteria->getSource();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $targetKey = $criteria->getSource();

        if (!$targetKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
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
            ->setMaxResults(1)
            ->where(
                $builder->expr()->isNull('r.deleted_at'),
                $builder->expr()->eq('r.source_id', ':source_key'),
                $builder->expr()->eq('r.target_id', ':target_key'),
                $builder->expr()->eq('e.type', ':type'),
            );

        $builder->setParameter('source_key', Uuid::fromHexToBytes($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('target_key', Uuid::fromHexToBytes($targetKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());

        $id = $builder->execute()->fetchColumn();

        if (!\is_string($id)) {
            return null;
        }

        return new RouteFindByTargetsAndTypeResult(new RouteStorageKey(Uuid::fromBytesToHex($id)));
    }
}
