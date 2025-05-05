<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview;
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
#[CoversClass(RouteOverview::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class RouteOverviewTest extends TestCase
{
    private const string ENTITY_TYPE_A = 'c6aad9f6355b4bf78f548a73caa502aa';

    private const string ENTITY_TYPE_B = '63b419caa57d4a08bd724604622473b7';

    private const string PORTAL_A = '4632d49df5d4430f9b498ecd44cc7c58';

    private const string PORTAL_B = 'b43cbc506680462c8a50513fa02032a6';

    private const string ROUTE_DELETED = '6b4bf85d1ea541ea85b5fed5ac34d2f4';

    private const string ROUTE_TYPE_A = '9f94ce0b915d4fe08223fb0be889daa3';

    private const string ROUTE_TYPE_B = 'c87fc36b80274ac09bf643847392d7e5';

    private const string ROUTE_FIRST = '1582c830042d49a3b3e48a489bc28cab';

    private const string ROUTE_LAST = '632348c2f449436e99d3c8e491ef942d';

    protected bool $setupQueryTracking = false;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $typeA = Id::toBinary(self::ENTITY_TYPE_A);
        $typeB = Id::toBinary(self::ENTITY_TYPE_B);
        $portalA = Id::toBinary(self::PORTAL_A);
        $portalB = Id::toBinary(self::PORTAL_B);
        $now = DateTime::nowToStorage();
        $yesterday = DateTime::toStorage(\date_create()->sub(new \DateInterval('P1D')));
        $tomorrow = DateTime::toStorage(\date_create()->add(new \DateInterval('P1D')));

        $connection->insert('heptaconnect_entity_type', [
            'id' => $typeA,
            'name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_entity_type', [
            'id' => $typeB,
            'name' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalA,
            'class_name' => self::class,
            'alias' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalB,
            'class_name' => TestCase::class,
            'alias' => TestCase::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $routeDeleted = Id::toBinary(self::ROUTE_DELETED);
        $routeTypeA = Id::toBinary(self::ROUTE_TYPE_A);
        $routeTypeB = Id::toBinary(self::ROUTE_TYPE_B);
        $routeFirst = Id::toBinary(self::ROUTE_FIRST);
        $routeLast = Id::toBinary(self::ROUTE_LAST);

        $connection->insert('heptaconnect_route', [
            'id' => $routeDeleted,
            'type_id' => $typeA,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $now,
            'deleted_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_route', [
            'id' => $routeTypeA,
            'type_id' => $typeA,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $now,
            'deleted_at' => null,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_route', [
            'id' => $routeTypeB,
            'type_id' => $typeB,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $now,
            'deleted_at' => null,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_route', [
            'id' => $routeFirst,
            'type_id' => $typeB,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $yesterday,
            'deleted_at' => null,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_route', [
            'id' => $routeLast,
            'type_id' => $typeA,
            'source_id' => $portalA,
            'target_id' => $portalB,
            'created_at' => $tomorrow,
            'deleted_at' => null,
        ], ['id' => Types::BINARY]);
    }

    public function testDeletedAt(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria = new RouteOverviewCriteria();
        static::assertCount(4, [...$action->overview($criteria)]);
    }

    public function testPagination(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria0 = new RouteOverviewCriteria();
        $criteria0->setPageSize(1);
        $criteria0->setPage(0);

        $criteria1 = clone $criteria0;
        $criteria1->setPage(1);

        $criteria2 = clone $criteria0;
        $criteria2->setPage(2);

        $criteria3 = clone $criteria0;
        $criteria3->setPage(3);

        $criteria4 = clone $criteria0;
        $criteria4->setPage(4);

        $criteria5 = clone $criteria0;
        $criteria5->setPage(5);

        static::assertCount(1, [...$action->overview($criteria0)]); // page 0 equals page 1
        static::assertCount(1, [...$action->overview($criteria1)]); // likely route type a
        static::assertCount(1, [...$action->overview($criteria2)]); // likely route type b
        static::assertCount(1, [...$action->overview($criteria3)]); // likely first route
        static::assertCount(1, [...$action->overview($criteria4)]); // likely last route
        static::assertCount(0, [...$action->overview($criteria5)]); // none
    }

    public function testSortByDateAsc(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria = new RouteOverviewCriteria();
        $criteria->setSort([
            RouteOverviewCriteria::FIELD_CREATED => RouteOverviewCriteria::SORT_ASC,
        ]);

        /** @var RouteOverviewResult $item */
        foreach ($action->overview($criteria) as $item) {
            static::assertTrue($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_FIRST)));

            break;
        }
    }

    public function testSortByDateDesc(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria = new RouteOverviewCriteria();
        $criteria->setSort([
            RouteOverviewCriteria::FIELD_CREATED => RouteOverviewCriteria::SORT_DESC,
        ]);

        /** @var RouteOverviewResult $item */
        foreach ($action->overview($criteria) as $item) {
            static::assertTrue($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_LAST)));

            break;
        }
    }

    public function testSortByEntityTypeAsc(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria = new RouteOverviewCriteria();
        $criteria->setSort([
            RouteOverviewCriteria::FIELD_ENTITY_TYPE => RouteOverviewCriteria::SORT_ASC,
        ]);

        $indexA = null;
        $indexB = null;

        /** @var RouteOverviewResult $item */
        foreach ($action->overview($criteria) as $index => $item) {
            if ($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_TYPE_A))) {
                $indexA = $index;
            }

            if ($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_TYPE_B))) {
                $indexB = $index;
            }
        }

        static::assertGreaterThan($indexA, $indexB);
    }

    public function testSortByEntityTypeDesc(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteOverviewAction();
        $criteria = new RouteOverviewCriteria();
        $criteria->setSort([
            RouteOverviewCriteria::FIELD_ENTITY_TYPE => RouteOverviewCriteria::SORT_DESC,
        ]);

        $indexA = null;
        $indexB = null;

        /** @var RouteOverviewResult $item */
        foreach ($action->overview($criteria) as $index => $item) {
            if ($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_TYPE_A))) {
                $indexA = $index;
            }

            if ($item->getRouteKey()->equals(new RouteStorageKey(self::ROUTE_TYPE_B))) {
                $indexB = $index;
            }
        }

        static::assertGreaterThan($indexB, $indexA);
    }
}
