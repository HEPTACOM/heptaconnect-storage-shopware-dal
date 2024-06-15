<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathIdResolver;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\WebHttpHandlerConfigurationTestContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
#[CoversClass(TestCase::class)]
#[CoversClass(WebHttpHandlerAccessor::class)]
#[CoversClass(WebHttpHandlerConfigurationFind::class)]
#[CoversClass(WebHttpHandlerConfigurationSet::class)]
#[CoversClass(WebHttpHandlerPathAccessor::class)]
#[CoversClass(WebHttpHandlerPathIdResolver::class)]
class WebHttpHandlerConfigurationTest extends WebHttpHandlerConfigurationTestContract
{
    #[\Override]
    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
