<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1636704625RemoveWebhookTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1636704625;
    }

    public function update(Connection $connection): void
    {
        $connection->getSchemaManager()->dropTable('heptaconnect_webhook');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
