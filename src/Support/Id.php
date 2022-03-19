<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support;

use Ramsey\Uuid\Uuid;

abstract class Id
{
    public static function toHex(string $binary): string
    {
        return \bin2hex($binary);
    }

    public static function toBinary(string $hex): string
    {
        return \hex2bin($hex);
    }

    /**
     * @param string[] $binaries
     *
     * @return string[]
     */
    public static function toHexList(array $binaries): array
    {
        return \array_map([self::class, 'toHex'], $binaries);
    }

    /**
     * @param string[] $hex
     *
     * @return string[]
     */
    public static function toBinaryList(array $hex): array
    {
        return \array_map([self::class, 'toBinary'], $hex);
    }

    /**
     * @param iterable<string> $binaries
     *
     * @return iterable<string>
     */
    public static function toHexIterable(iterable $binaries): iterable
    {
        return \iterable_map($binaries, [self::class, 'toHex']);
    }

    /**
     * @param iterable<string> $hex
     *
     * @return iterable<string>
     */
    public static function toBinaryIterable(iterable $hex): iterable
    {
        return \iterable_map([self::class, 'toBinary'], $hex);
    }

    public static function randomHex(): string
    {
        return static::toHex(static::randomBinary());
    }

    public static function randomBinary(): string
    {
        return Uuid::uuid4()->getBytes();
    }

    public static function hashedHex(string $seed, string $hashable): string
    {
        return static::toHex(static::hashedBinary($seed, $hashable));
    }

    public static function hashedBinary(string $seed, string $hashable): string
    {
        return Uuid::uuid5($seed, $hashable)->getBytes();
    }
}
