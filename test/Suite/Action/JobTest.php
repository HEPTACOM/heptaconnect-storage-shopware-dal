<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFail;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinish;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobSchedule;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobStart;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\JobTestContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(JobCreate::class)]
#[CoversClass(JobDelete::class)]
#[CoversClass(JobFail::class)]
#[CoversClass(JobFinish::class)]
#[CoversClass(JobFinishedList::class)]
#[CoversClass(JobGet::class)]
#[CoversClass(JobSchedule::class)]
#[CoversClass(JobStart::class)]
#[CoversClass(JobStateEnum::class)]
#[CoversClass(JobTypeAccessor::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteCapabilityAccessor::class)]
#[CoversClass(RouteCreate::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
#[CoversClass(TestCase::class)]
class JobTest extends JobTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
