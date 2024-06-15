<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\RouteTestContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(ReceptionRouteList::class)]
#[CoversClass(RouteCapabilityAccessor::class)]
#[CoversClass(RouteCreate::class)]
#[CoversClass(RouteDelete::class)]
#[CoversClass(RouteFind::class)]
#[CoversClass(RouteGet::class)]
#[CoversClass(RouteOverview::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
#[CoversClass(TestCase::class)]
class RouteTest extends RouteTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
