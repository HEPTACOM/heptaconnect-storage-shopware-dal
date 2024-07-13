<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\RouteKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteGet::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class RouteGetTest extends TestCase
{
    private const string ENTITY_TYPE = 'c6aad9f6355b4bf78f548a73caa502aa';

    private const string PORTAL_A = '4632d49df5d4430f9b498ecd44cc7c58';

    private const string PORTAL_B = 'b43cbc506680462c8a50513fa02032a6';

    private const string ROUTE_DELETED = '6b4bf85d1ea541ea85b5fed5ac34d2f4';

    private const string ROUTE_ACTIVE = '9f94ce0b915d4fe08223fb0be889daa3';

    #[\Override]
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

        /** @var RouteGetResult $item */
        foreach ($action->get($criteria) as $item) {
            static::assertTrue(Simple::class()->equals($item->getEntityType()));
        }
    }
}
