<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class RouteFindTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Id::randomBinary(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => $now,
            'deleted_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getRouteFindAction();
        $criteria = new RouteFindCriteria(
            new PortalNodeStorageKey($portalNodeHex),
            new PortalNodeStorageKey($portalNodeHex),
            (new class () extends DatasetEntityContract { })::class()
        );
        static::assertNull($action->find($criteria));
    }

    public function testFind(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Id::randomBinary(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getRouteFindAction();
        $criteria = new RouteFindCriteria(
            new PortalNodeStorageKey($portalNodeHex),
            new PortalNodeStorageKey($portalNodeHex),
            new UnsafeClassString(self::class)
        );
        static::assertInstanceOf(RouteFindResult::class, $action->find($criteria));
    }
}
