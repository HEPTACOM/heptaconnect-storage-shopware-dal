<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\IdentityErrorKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\IdentityRedirectKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(FileReferenceRequestStorageKey::class)]
#[CoversClass(Id::class)]
#[CoversClass(IdentityErrorStorageKey::class)]
#[CoversClass(IdentityRedirectStorageKey::class)]
#[CoversClass(JobStorageKey::class)]
#[CoversClass(MappingNodeStorageKey::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteStorageKey::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
class StorageKeyGeneratorTest extends TestCase
{
    protected bool $setupQueryTracking = false;

    public function testUnsupportedClassException(): void
    {
        $this->expectException(UnsupportedStorageKeyException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Unsupported storage key class: ' . AbstractStorageKey::class);

        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        $keys = \iterable_to_array($generator->generateKeys(AbstractStorageKey::class, 1));
    }

    public function testPreviewKeySerialization(): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        $serialized = $generator->serialize(new PreviewPortalNodeKey(Portal::class()));

        static::assertStringContainsString(\addcslashes(Portal::class, '\\'), $serialized);
    }

    public function testPreviewKeyDeserialization(): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        $deserialized = $generator->deserialize('{"preview":"Heptacom\\\\HeptaConnect\\\\Storage\\\\ShopwareDal\\\\Test\\\\Fixture\\\\Portal\\\\Portal"}');

        static::assertInstanceOf(PreviewPortalNodeKey::class, $deserialized);
        /* @var $deserialized PreviewPortalNodeKey */
        static::assertTrue(Portal::class()->equals($deserialized->getPortalType()));
    }

    #[DataProvider('provideKeyInterfaces')]
    public function testKeyGenerator(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        static::assertInstanceOf($interface, $key);
    }

    #[DataProvider('provideKeyInterfaces')]
    public function testKeyGeneratorList(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /* @var AbstractStorageKey $key */
        static::assertCount(100, $generator->generateKeys($interface, 100));
        static::assertCount(10, $generator->generateKeys($interface, 10));
        static::assertCount(0, $generator->generateKeys($interface, 0));
        static::assertCount(0, $generator->generateKeys($interface, -10));
    }

    #[DataProvider('provideKeyInterfaces')]
    public function testKeySerialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        $serialized = $generator->serialize($key);
        static::assertStringContainsString($key->getUuid(), $serialized);
    }

    #[DataProvider('provideKeyInterfaces')]
    public function testKeyDeserialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        $serialized = $generator->serialize($key);
        $deserialized = $generator->deserialize($serialized);
        static::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    #[DataProvider('provideKeyInterfaces')]
    public function testKeyJsonSerialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        static::assertStringContainsString($key->getUuid(), \json_encode($key, \JSON_THROW_ON_ERROR));
    }

    public static function provideKeyInterfaces(): iterable
    {
        yield [PortalNodeKeyInterface::class];
        yield [MappingNodeKeyInterface::class];
        yield [RouteKeyInterface::class];
        yield [IdentityRedirectKeyInterface::class];
        yield [IdentityErrorKeyInterface::class];
        yield [JobKeyInterface::class];
        yield [FileReferenceRequestKeyInterface::class];
    }

    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
