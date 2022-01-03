<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\RouteKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class RouteGetTest extends TestCase
{
    private const ENTITY_TYPE = 'c6aad9f6355b4bf78f548a73caa502aa';

    private const PORTAL_A = '4632d49df5d4430f9b498ecd44cc7c58';

    private const PORTAL_B = 'b43cbc506680462c8a50513fa02032a6';

    private const ROUTE_DELETED = '6b4bf85d1ea541ea85b5fed5ac34d2f4';

    private const ROUTE_ACTIVE = '9f94ce0b915d4fe08223fb0be889daa3';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->kernel->getContainer()->get(Connection::class);
        $type = Uuid::fromHexToBytes(self::ENTITY_TYPE);
        $portalA = Uuid::fromHexToBytes(self::PORTAL_A);
        $portalB = Uuid::fromHexToBytes(self::PORTAL_B);
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_entity_type', [
            'id' => $type,
            'type' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalA,
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalB,
            'class_name' => TestCase::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $routeDeleted = Uuid::fromHexToBytes(self::ROUTE_DELETED);
        $routeActive = Uuid::fromHexToBytes(self::ROUTE_ACTIVE);

        $connection->insert('heptaconnect_route', [
            'id' => $routeDeleted,
            'type_id' => $type,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $now,
            'deleted_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_route', [
            'id' => $routeActive,
            'type_id' => $type,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $now,
            'deleted_at' => null,
        ], ['id' => Types::BINARY]);
    }

    public function testDeletedAt(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $action = new RouteGet($connection, new QueryIterator());
        $criteria = new RouteGetCriteria(new RouteKeyCollection([new RouteStorageKey(self::ROUTE_DELETED)]));

        static::assertCount(0, $action->get($criteria));
    }

    public function testGet(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new RouteGet($connection, new QueryIterator());
        $criteria = new RouteGetCriteria(new RouteKeyCollection([new RouteStorageKey(self::ROUTE_ACTIVE)]));

        /** @var \Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetResult $item */
        foreach ($action->get($criteria) as $item) {
            static::assertSame(Simple::class, $item->getEntityType());
        }
    }
}
