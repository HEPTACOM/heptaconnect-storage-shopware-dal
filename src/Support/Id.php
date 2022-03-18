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
