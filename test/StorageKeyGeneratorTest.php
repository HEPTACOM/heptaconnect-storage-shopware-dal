<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\IdentityErrorKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityErrorStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class StorageKeyGeneratorTest extends TestCase
{
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

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGenerator(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        static::assertInstanceOf($interface, $key);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGeneratorList(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /* @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        static::assertCount(100, $generator->generateKeys($interface, 100));
        static::assertCount(10, $generator->generateKeys($interface, 10));
        static::assertCount(0, $generator->generateKeys($interface, 0));
        static::assertCount(0, $generator->generateKeys($interface, -10));
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeySerialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        $serialized = $generator->serialize($key);
        static::assertStringContainsString($key->getUuid(), $serialized);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyDeserialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        $serialized = $generator->serialize($key);
        $deserialized = $generator->deserialize($serialized);
        static::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyJsonSerialization(string $interface): void
    {
        $generator = $this->createStorageFacade()->getStorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = \iterable_to_array($generator->generateKeys($interface, 1))[0];
        static::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function provideKeyInterfaces(): iterable
    {
        yield [PortalNodeKeyInterface::class];
        yield [MappingNodeKeyInterface::class];
        yield [RouteKeyInterface::class];
        yield [IdentityErrorKeyInterface::class];
        yield [JobKeyInterface::class];
        yield [FileReferenceRequestKeyInterface::class];
    }

    protected function createStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
