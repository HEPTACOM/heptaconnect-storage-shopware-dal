<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\RouteTestContract;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class RouteTest extends RouteTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
