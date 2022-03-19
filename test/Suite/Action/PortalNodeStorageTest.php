<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\PortalNodeStorageTestContract;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageClear
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageSet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class PortalNodeStorageTest extends PortalNodeStorageTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);

        return new StorageFacade($connection);
    }
}
