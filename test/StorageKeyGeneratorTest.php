<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingExceptionStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 */
class StorageKeyGeneratorTest extends TestCase
{
    public function testUnsupportedClassException(): void
    {
        $this->expectException(UnsupportedStorageKeyException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Unsupported storage key class: '.AbstractStorageKey::class);

        $generator = new StorageKeyGenerator();
        $generator->generateKey(AbstractStorageKey::class);
    }

    public function testPreviewKeySerialization(): void
    {
        $generator = new StorageKeyGenerator();
        $serialized = $generator->serialize(new PreviewPortalNodeKey(PortalContract::class));

        self::assertStringContainsString(\addcslashes(PortalContract::class, '\\'), $serialized);
    }

    public function testPreviewKeyDeserialization(): void
    {
        $generator = new StorageKeyGenerator();
        $deserialized = $generator->deserialize('{"preview":"Heptacom\\\\HeptaConnect\\\\Portal\\\\Base\\\\Portal\\\\Contract\\\\PortalContract"}');

        self::assertInstanceOf(PreviewPortalNodeKey::class, $deserialized);
        /* @var $deserialized PreviewPortalNodeKey */
        self::assertSame(PortalContract::class, $deserialized->getPortalType());
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGenerator(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        self::assertInstanceOf($interface, $key);
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyGeneratorList(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /* @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        self::assertCount(100, $generator->generateKeys($interface, 100));
        self::assertCount(10, $generator->generateKeys($interface, 10));
        self::assertCount(0, $generator->generateKeys($interface, 0));
        self::assertCount(0, $generator->generateKeys($interface, -10));
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
        self::assertStringContainsString($key->getUuid(), $serialized);
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
        self::assertTrue($key->equals($deserialized), 'Keys are not equal');
    }

    /**
     * @dataProvider provideKeyInterfaces
     */
    public function testKeyJsonSerialization(string $interface): void
    {
        $generator = new StorageKeyGenerator();
        /** @var \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey $key */
        $key = $generator->generateKey($interface);
        self::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function provideKeyInterfaces(): iterable
    {
        yield [PortalNodeKeyInterface::class];
        yield [WebhookKeyInterface::class];
        yield [MappingNodeKeyInterface::class];
        yield [CronjobKeyInterface::class];
        yield [CronjobRunKeyInterface::class];
        yield [RouteKeyInterface::class];
        yield [MappingKeyInterface::class];
        yield [MappingExceptionKeyInterface::class];
        yield [JobKeyInterface::class];
        yield [JobPayloadKeyInterface::class];
    }
}
