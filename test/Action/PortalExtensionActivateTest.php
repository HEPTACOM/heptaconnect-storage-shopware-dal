<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\PortalExtension\PortalExtension;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class PortalExtensionActivateTest extends TestCase
{
    public function testActivateWithoutConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionActivateAction();
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        static::assertCount(1, $result->getPassedActivations());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            0,
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('0', $activeEntries);
    }

    public function testActivateWithPreviousDeactivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_portal_node_extension', [
            'id' => Uuid::randomBytes(),
            'portal_node_id' => $portalNode,
            'active' => 0,
            'class_name' => PortalExtension::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionActivateAction();
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        static::assertCount(1, $result->getPassedActivations());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            0,
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('0', $activeEntries);
    }

    public function testActivateWithPreviousActivatedConfiguration(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'configuration' => '{}',
            'class_name' => Portal::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_portal_node_extension', [
            'id' => Uuid::randomBytes(),
            'portal_node_id' => $portalNode,
            'active' => 1,
            'class_name' => PortalExtension::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);

        $action = $facade->getPortalExtensionActivateAction();
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        static::assertCount(1, $result->getPassedActivations());
        static::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            0,
            [
                'id' => Types::BINARY,
            ]
        );

        static::assertSame('1', $activeEntries);
    }
}
