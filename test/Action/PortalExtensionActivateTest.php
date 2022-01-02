<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\PortalExtension\PortalExtension;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive
 */
class PortalExtensionActivateTest extends TestCase
{
    public function testActivateWithoutConfiguration(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => Portal::class,
            'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
        ]);

        $action = new PortalExtensionActivate($connection);
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        self::assertCount(1, $result->getPassedActivations());
        self::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        self::assertSame(1, $activeEntries);
    }

    public function testActivateWithPreviousDeactivatedConfiguration(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
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

        $action = new PortalExtensionActivate($connection);
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        self::assertCount(1, $result->getPassedActivations());
        self::assertTrue($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        self::assertSame(1, $activeEntries);
    }

    public function testActivateWithPreviousActivatedConfiguration(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNode = Uuid::randomBytes();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
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

        $action = new PortalExtensionActivate($connection);
        $payload = new PortalExtensionActivatePayload(new PortalNodeStorageKey(\bin2hex($portalNode)));
        $payload->addExtension(self::class);
        $result = $action->activate($payload);

        self::assertCount(0, $result->getPassedActivations());
        self::assertFalse($result->isSuccess());

        $activeEntries = $connection->fetchColumn(
            'SELECT count(1) FROM heptaconnect_portal_node_extension WHERE class_name = :className AND portal_node_id = :id AND active',
            [
                'className' => PortalExtension::class,
                'id' => $portalNode,
            ],
            [
                'id' => Types::BINARY,
            ]
        );

        self::assertSame(1, $activeEntries);
    }
}
