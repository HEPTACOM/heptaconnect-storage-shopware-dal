<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 */
class RouteCreateTest extends TestCase
{
    protected bool $setupQueryTracking = false;

    public function testCreate(): void
    {
        $source = Uuid::randomBytes();
        $target = Uuid::randomBytes();
        $entityType = Uuid::randomBytes();
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $source,
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $target,
            'class_name' => TestCase::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $sourceHex = Uuid::fromBytesToHex($source);
        $targetHex = Uuid::fromBytesToHex($target);

        /** @var EntityRepositoryInterface $entityTypes */
        $entityTypes = $this->kernel->getContainer()->get('heptaconnect_entity_type.repository');

        $action = new RouteCreate($connection, new StorageKeyGenerator(),  new EntityTypeAccessor($entityTypes), new RouteCapabilityAccessor($connection));
        \iterable_to_array($action->create(new RouteCreatePayloads([
            new RouteCreatePayload(new PortalNodeStorageKey($sourceHex), new PortalNodeStorageKey($targetHex), Simple::class, [RouteCapability::RECEPTION]),
            new RouteCreatePayload(new PortalNodeStorageKey($targetHex), new PortalNodeStorageKey($sourceHex), Simple::class),
        ])));

        $count = (int) $connection->executeQuery('SELECT count(1) FROM heptaconnect_route')->fetchColumn();
        self::assertSame(2, $count);
        $count = (int) $connection->executeQuery('SELECT count(1) FROM heptaconnect_route_has_capability')->fetchColumn();
        self::assertSame(1, $count);
    }
}