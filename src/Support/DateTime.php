<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support;

abstract class DateTime
{
    public static function nowToStorage(): string
    {
        return (string) static::toStorage(new \DateTimeImmutable());
    }

    public static function toStorage(\DateTimeInterface $dateTime): ?string
    {
        return $dateTime->format('Y-m-d H:i:s.v');
    }

    public static function fromStorage(string $string): \DateTimeInterface
    {
        return \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.v', $string);
    }
}
