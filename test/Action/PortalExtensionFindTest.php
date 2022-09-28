<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\PortalExtension\PortalExtension;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionFind
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class PortalExtensionFindTest extends TestCase
{
    public function testWithoutConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => Portal::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionFindAction();
        $result = $action->find(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $portalExtension = new PortalExtension();

        static::assertTrue($result->isActive($portalExtension));
    }

    public function testWithDeactivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => Portal::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_portal_node_extension', [
            'id' => Id::randomBinary(),
            'portal_node_id' => $portalNode,
            'active' => 0,
            'class_name' => PortalExtension::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionFindAction();
        $result = $action->find(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $portalExtension = new PortalExtension();

        static::assertFalse($result->isActive($portalExtension));
    }

    public function testWithActivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => Portal::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_portal_node_extension', [
            'id' => Id::randomBinary(),
            'portal_node_id' => $portalNode,
            'active' => 1,
            'class_name' => PortalExtension::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionFindAction();
        $result = $action->find(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $portalExtension = new PortalExtension();

        static::assertTrue($result->isActive($portalExtension));
    }
}
