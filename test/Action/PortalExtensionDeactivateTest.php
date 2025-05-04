<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Deactivate\PortalExtensionDeactivatePayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionDeactivate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\PortalExtension\PortalExtension;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalExtensionActivate::class)]
#[CoversClass(PortalExtensionDeactivate::class)]
#[CoversClass(PortalExtensionSwitchActive::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class PortalExtensionDeactivateTest extends TestCase
{
    public function testDeactivateWithoutConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionDeactivateAction();
        $payload = new PortalExtensionDeactivatePayload(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $payload->addExtension((new class() extends PortalExtension {
        })::class());
        $result = $action->deactivate($payload);

        static::assertSame(1, $result->getPassedDeactivations()->count());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchOne(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND NOT active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('0', $activeEntries);
    }

    public function testDeactivateWithPreviousDeactivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
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

        $action = $facade->getPortalExtensionDeactivateAction();
        $payload = new PortalExtensionDeactivatePayload(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $payload->addExtension((new class() extends PortalExtension {
        })::class());
        $result = $action->deactivate($payload);

        static::assertSame(1, $result->getPassedDeactivations()->count());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchOne(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND NOT active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('1', $activeEntries);
    }

    public function testDeactivateWithPreviousActivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
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

        $action = $facade->getPortalExtensionDeactivateAction();
        $payload = new PortalExtensionDeactivatePayload(new PortalNodeStorageKey(Id::toHex($portalNode)));
        $payload->addExtension((new class() extends PortalExtension {
        })::class());
        $result = $action->deactivate($payload);

        static::assertSame(1, $result->getPassedDeactivations()->count());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchOne(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND NOT active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('0', $activeEntries);
    }
}
