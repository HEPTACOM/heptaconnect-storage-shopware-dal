<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapabilityOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapabilityOverview
 */
class RouteCapabilityOverviewTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new RouteCapabilityOverview($connection);
        $criteria = new RouteCapabilityOverviewCriteria();
        static::assertCount(1, $action->overview($criteria));
    }

    public function testPagination(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new RouteCapabilityOverview($connection);
        $criteria0 = new RouteCapabilityOverviewCriteria();
        $criteria0->setPageSize(1);
        $criteria0->setPage(0);

        $criteria1 = clone $criteria0;
        $criteria1->setPage(1);

        static::assertCount(1, $action->overview($criteria0));
        static::assertCount(0, $action->overview($criteria1));
    }
}
