<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\RouteKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
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

        $connection = $this->getConnection();
        $type = Id::toBinary(self::ENTITY_TYPE);
        $portalA = Id::toBinary(self::PORTAL_A);
        $portalB = Id::toBinary(self::PORTAL_B);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_entity_type', [
            'id' => $type,
            'type' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalA,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalB,
            'class_name' => TestCase::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $routeDeleted = Id::toBinary(self::ROUTE_DELETED);
        $routeActive = Id::toBinary(self::ROUTE_ACTIVE);

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
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteGetAction();
        $criteria = new RouteGetCriteria(new RouteKeyCollection([new RouteStorageKey(self::ROUTE_DELETED)]));

        static::assertCount(0, $action->get($criteria));
    }

    public function testGet(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteGetAction();
        $criteria = new RouteGetCriteria(new RouteKeyCollection([new RouteStorageKey(self::ROUTE_ACTIVE)]));

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult $item */
        foreach ($action->get($criteria) as $item) {
            static::assertSame(Simple::class, $item->getEntityType());
        }
    }
}
