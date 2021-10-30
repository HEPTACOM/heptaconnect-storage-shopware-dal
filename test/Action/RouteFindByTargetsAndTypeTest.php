<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteFindByTargetsAndType;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteFindByTargetsAndType
 */
class RouteFindByTargetsAndTypeTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNode = Uuid::randomBytes();
        $portalNodeHex = Uuid::fromBytesToHex($portalNode);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Uuid::randomBytes();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Uuid::randomBytes(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'deleted_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = new RouteFindByTargetsAndType($connection);
        $criteria = new RouteFindByTargetsAndTypeCriteria(new PortalNodeStorageKey($portalNodeHex), new PortalNodeStorageKey($portalNodeHex), self::class);
        static::assertNull($action->find($criteria));
    }

    public function testFind(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNode = Uuid::randomBytes();
        $portalNodeHex = Uuid::fromBytesToHex($portalNode);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Uuid::randomBytes();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Uuid::randomBytes(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = new RouteFindByTargetsAndType($connection);
        $criteria = new RouteFindByTargetsAndTypeCriteria(new PortalNodeStorageKey($portalNodeHex), new PortalNodeStorageKey($portalNodeHex), self::class);
        static::assertInstanceOf(RouteFindByTargetsAndTypeResult::class, $action->find($criteria));
    }
}
