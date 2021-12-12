<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum;

abstract class JobStateEnum
{
    private static ?string $started = null;

    private static ?string $failed = null;

    private static ?string $finished = null;

    public static function open(): ?string
    {
        return null;
    }

    public static function started(): string
    {
        return self::$started ??= \hex2bin('ca5ef83ffd114913a81477efafa14272');
    }

    public static function failed(): string
    {
        return self::$failed ??= \hex2bin('28ff3666f0f746cf84770c1148600605');
    }

    public static function finished(): string
    {
        return self::$finished ??= \hex2bin('6575ad837c71416f887d0e516a1bd813');
    }
}
