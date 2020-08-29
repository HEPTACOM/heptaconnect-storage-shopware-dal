<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
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

    public function testCronjobKey(): void
    {
        $generator = new StorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = $generator->generateKey(CronjobKeyInterface::class);
        self::assertInstanceOf(CronjobStorageKey::class, $key);
        self::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function testMappingNodeKey(): void
    {
        $generator = new StorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = $generator->generateKey(MappingNodeKeyInterface::class);
        self::assertInstanceOf(MappingNodeStorageKey::class, $key);
        self::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function testPortalNodeKey(): void
    {
        $generator = new StorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = $generator->generateKey(PortalNodeKeyInterface::class);
        self::assertInstanceOf(PortalNodeStorageKey::class, $key);
        self::assertStringContainsString($key->getUuid(), \json_encode($key));
    }

    public function testWebhookKey(): void
    {
        $generator = new StorageKeyGenerator();
        /** @var AbstractStorageKey $key */
        $key = $generator->generateKey(WebhookKeyInterface::class);
        self::assertInstanceOf(WebhookStorageKey::class, $key);
        self::assertStringContainsString($key->getUuid(), \json_encode($key));
    }
}
