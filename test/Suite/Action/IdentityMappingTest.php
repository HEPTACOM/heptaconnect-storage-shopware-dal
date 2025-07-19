<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Reflect\IdentityReflectPayload;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityRedirect\IdentityRedirectCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityRedirect\IdentityRedirectDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeySerializer;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\IdentityMappingTestContract;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(IdentityMap::class)]
#[CoversClass(IdentityPersist::class)]
#[CoversClass(IdentityRedirectCreate::class)]
#[CoversClass(IdentityRedirectDelete::class)]
#[CoversClass(IdentityReflect::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeGet::class)]
#[CoversClass(PortalNodeList::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeySerializer::class)]
#[CoversClass(TestCase::class)]
class IdentityMappingTest extends IdentityMappingTestContract
{
    public function testNoDatabaseLookupsOnEmptyPayload(): void
    {
        $this->expectNotToPerformDatabaseQueries();
        $reflector = $this->createStorageFacade()->getIdentityReflectAction();
        $reflector->reflect(new IdentityReflectPayload(new PortalNodeStorageKey(Id::randomHex()), new MappedDatasetEntityCollection()));

        static::assertSame([], $this->trackedQueries);
    }

    #[\Override]
    public function testReflectTwoEntitiesOfSameTypeFromPortalNodeAToBWithIdentityRedirects(): void
    {
        parent::testReflectTwoEntitiesOfSameTypeFromPortalNodeAToBWithIdentityRedirects();

        // TODO look and decide whether no used indices is fine
        $this->expectNotToPerformDatabaseQueries();
        $this->trackedQueries = [];
    }

    #[\Override]
    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
