<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Shopware\Core\Framework\Migration\MigrationSource as ShopwareMigrationSource;

class MigrationSource extends ShopwareMigrationSource
{
    public function __construct()
    {
        parent::__construct('HeptaConnectStorage', [
            __DIR__ . '/Migration' => 'Heptacom\HeptaConnect\Storage\ShopwareDal\Migration',
        ]);
    }
}
