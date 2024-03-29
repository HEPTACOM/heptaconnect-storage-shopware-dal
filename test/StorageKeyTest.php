<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 */
class StorageKeyTest extends TestCase
{
    /**
     * @dataProvider provideStorageKeys
     */
    public function testKeyUuidSetter(AbstractStorageKey $key): void
    {
        static::assertEquals('abc', $key->getUuid());
        $key->setUuid('xyz');
        static::assertEquals('xyz', $key->getUuid());
    }

    /**
     * @dataProvider provideStorageKeys
     */
    public function testKeyEquals(AbstractStorageKey $key): void
    {
        static::assertTrue($key->equals($key));
        static::assertFalse($key->equals(new class('xyz') extends AbstractStorageKey {
        }));
    }

    public function provideStorageKeys(): iterable
    {
        yield [new MappingNodeStorageKey('abc')];
        yield [new PortalNodeStorageKey('abc')];
    }
}
