<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Ramsey\Uuid\Uuid;

class WebHttpHandlerPathIdResolver
{
    public const ID_NS = '7e7507b2-17b9-486c-865f-e1bbb98f6c96';

    public function getIdFromPath(string $path): string
    {
        return (string) Uuid::uuid5(self::ID_NS, $path)->getHex();
    }
}
