<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

abstract class JobStateEnum
{
    private static ?string $open = null;

    private static ?string $started = null;

    private static ?string $failed = null;

    private static ?string $finished = null;

    public static function open(): string
    {
        return self::$open ??= Id::toBinary('3aee495720734539b98f0605c33e59d2');
    }

    public static function started(): string
    {
        return self::$started ??= Id::toBinary('ca5ef83ffd114913a81477efafa14272');
    }

    public static function failed(): string
    {
        return self::$failed ??= Id::toBinary('28ff3666f0f746cf84770c1148600605');
    }

    public static function finished(): string
    {
        return self::$finished ??= Id::toBinary('6575ad837c71416f887d0e516a1bd813');
    }
}
