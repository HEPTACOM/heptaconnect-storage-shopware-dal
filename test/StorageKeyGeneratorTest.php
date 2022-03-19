<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\IdentityErrorKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityErrorStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 */
class StorageKeyGeneratorTest extends TestCase
{
    public function testUnsupportedClassException(): void
    {
        $this->expectException(UnsupportedStorageKeyException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Unsupported storage key class: ' . AbstractStorageKey::class);

        $generator = new StorageKeyGenerator();
        $generator->generateKey(AbstractStorageKey::class);
    }

    public function testPreviewKeySerialization(): void
    {
        $generator = new StorageKeyGenerator();
        $serialized = $generator->serialize(new PreviewPortalNodeKey(PortalContract::class));

        static::assertStringContainsString(\addcslashes(PortalContract::class, '\\'), $serialized);
    }

    public function testPreviewKeyDeserialization(): void
    {
        $generator = new StorageKeyGenerator();
        $deserialized = $generator->deserialize('{"preview":"Heptacom\\\\HeptaConnect\\\\Portal\\\\Base\\\\Portal\\\\Contract\\\\PortalContract"}');

        static::assertInstanceOf(PreviewPortalNodeKey::class, $deserialized);
        /* @var $deserialized PreviewPortalNodeKey */
        static::assertSame(PortalContract::class, $deserialized->getPortalType());
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGenerator(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        static::assertInstanceOf($interface, $key);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGeneratorList(string $interface): void
    {
        $generator = new StorageKeyGenerator();
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
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        $serialized = $generator->serialize($key);
        static::assertStringContainsString($key->getUuid(), $serialized);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyDeserialization(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        $serialized = $generator->serialize($key);
        $deserialized = $generator->deserialize($serialized);
        static::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyJsonSerialization(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        static::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function provideKeyInterfaces(): iterable
    {
        yield [PortalNodeKeyInterface::class];
        yield [MappingNodeKeyInterface::class];
        yield [RouteKeyInterface::class];
        yield [IdentityErrorKeyInterface::class];
        yield [JobKeyInterface::class];
    }
}
