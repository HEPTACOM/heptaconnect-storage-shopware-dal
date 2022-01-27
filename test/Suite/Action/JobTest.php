<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\JobTestContract;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFail
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinish
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobSchedule
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobStart
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class JobTest extends JobTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);

        return new StorageFacade($connection);
    }
}