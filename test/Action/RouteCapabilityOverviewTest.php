<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\RouteCapability\Overview\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 */
class RouteCapabilityOverviewTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $facade = new StorageFacade($connection);
        $action = $facade->getRouteCapabilityOverviewAction();
        $criteria = new RouteCapabilityOverviewCriteria();
        static::assertCount(1, $action->overview($criteria));
    }

    public function testPagination(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $facade = new StorageFacade($connection);
        $action = $facade->getRouteCapabilityOverviewAction();
        $criteria0 = new RouteCapabilityOverviewCriteria();
        $criteria0->setPageSize(1);
        $criteria0->setPage(0);

        $criteria1 = clone $criteria0;
        $criteria1->setPage(1);

        static::assertCount(1, $action->overview($criteria0));
        static::assertCount(0, $action->overview($criteria1));
    }
}
