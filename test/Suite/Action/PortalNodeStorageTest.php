<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageClear;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageSet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\PortalNodeStorageTestContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeStorageClear::class)]
#[CoversClass(PortalNodeStorageDelete::class)]
#[CoversClass(PortalNodeStorageGet::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(PortalNodeStorageList::class)]
#[CoversClass(PortalNodeStorageSet::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
#[CoversClass(TestCase::class)]
class PortalNodeStorageTest extends PortalNodeStorageTestContract
{
    public function testUsageOfPreviewPortalFails(): void
    {
        $this->expectNotToPerformDatabaseQueries();
        parent::testUsageOfPreviewPortalFails();
    }

    protected function createStorageFacade(): StorageFacadeInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);

        return new StorageFacade($connection);
    }
}
