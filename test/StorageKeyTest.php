<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(MappingNodeStorageKey::class)]
#[CoversClass(PortalNodeStorageKey::class)]
class StorageKeyTest extends TestCase
{
    #[DataProvider('provideStorageKeys')]
    public function testKeyUuidSetter(AbstractStorageKey $key): void
    {
        static::assertEquals('abc', $key->getUuid());
        $key->setUuid('xyz');
        static::assertEquals('xyz', $key->getUuid());
    }

    #[DataProvider('provideStorageKeys')]
    public function testKeyEquals(AbstractStorageKey $key): void
    {
        static::assertTrue($key->equals($key));
        static::assertFalse($key->equals(new class('xyz') extends AbstractStorageKey {
        }));
    }

    public static function provideStorageKeys(): iterable
    {
        yield [new MappingNodeStorageKey('abc')];
        yield [new PortalNodeStorageKey('abc')];
    }
}
