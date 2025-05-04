<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityErrorStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityRedirectStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeySerializer;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(FileReferenceRequestStorageKey::class)]
#[CoversClass(Id::class)]
#[CoversClass(IdentityErrorStorageKey::class)]
#[CoversClass(IdentityRedirectStorageKey::class)]
#[CoversClass(JobStorageKey::class)]
#[CoversClass(MappingNodeStorageKey::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteStorageKey::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeySerializer::class)]
class StorageKeySerializerTest extends TestCase
{
    protected bool $setupQueryTracking = false;

    public function testPreviewKeySerialization(): void
    {
        $generator = $this->createStorageFacade()->getStorageKeySerializer();
        $serialized = $generator->serialize(new PreviewPortalNodeKey(Portal::class()));

        static::assertStringContainsString(\addcslashes(Portal::class, '\\'), $serialized);
    }

    public function testPreviewKeyDeserialization(): void
    {
        $generator = $this->createStorageFacade()->getStorageKeySerializer();
        $deserialized = $generator->deserialize('{"preview":"Heptacom\\\\HeptaConnect\\\\Storage\\\\ShopwareDal\\\\Test\\\\Fixture\\\\Portal\\\\Portal"}');

        static::assertInstanceOf(PreviewPortalNodeKey::class, $deserialized);
        /* @var $deserialized PreviewPortalNodeKey */
        static::assertTrue(Portal::class()->equals($deserialized->getPortalType()));
    }

    #[DataProvider('provideKeys')]
    public function testKeySerialization(AbstractStorageKey $key): void
    {
        $generator = $this->createStorageFacade()->getStorageKeySerializer();
        $serialized = $generator->serialize($key);
        static::assertStringContainsString($key->getUuid(), $serialized);
    }

    #[DataProvider('provideKeys')]
    public function testKeyDeserialization(AbstractStorageKey $key): void
    {
        $generator = $this->createStorageFacade()->getStorageKeySerializer();
        $serialized = $generator->serialize($key);
        $deserialized = $generator->deserialize($serialized);
        static::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    #[DataProvider('provideKeys')]
    public function testKeyJsonSerialization(AbstractStorageKey $key): void
    {
        static::assertStringContainsString($key->getUuid(), \json_encode($key, \JSON_THROW_ON_ERROR));
    }

    public static function provideKeys(): iterable
    {
        yield [new PortalNodeStorageKey(Id::randomHex())];
        yield [new MappingNodeStorageKey(Id::randomHex())];
        yield [new RouteStorageKey(Id::randomHex())];
        yield [new IdentityRedirectStorageKey(Id::randomHex())];
        yield [new IdentityErrorStorageKey(Id::randomHex())];
        yield [new JobStorageKey(Id::randomHex())];
        yield [new FileReferenceRequestStorageKey(Id::randomHex())];
    }

    protected function createStorageFacade(): StorageFacadeInterface
    {
        return StorageFacadeProvider::createContainerStorageFacade($this->getConnection());
    }
}
