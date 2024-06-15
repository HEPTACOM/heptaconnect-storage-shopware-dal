<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Heptacom\HeptaConnect\Storage\Base\Action\RouteCapability\Overview\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteCapabilityOverview::class)]
#[CoversClass(StorageFacade::class)]
class RouteCapabilityOverviewTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteCapabilityOverviewAction();
        $criteria = new RouteCapabilityOverviewCriteria();
        static::assertCount(1, $action->overview($criteria));
    }

    public function testPagination(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getRouteCapabilityOverviewAction();
        $criteria0 = new RouteCapabilityOverviewCriteria();
        $criteria0->setPageSize(1);
        $criteria0->setPage(0);

        $criteria1 = clone $criteria0;
        $criteria1->setPage(1);

        $criteria2 = clone $criteria0;
        $criteria2->setPage(2);

        static::assertCount(1, $action->overview($criteria0));
        static::assertCount(1, $action->overview($criteria1));
        static::assertCount(0, $action->overview($criteria2));
    }
}
