<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635019152PortalNodeStorageIndexHappenedAtColumns extends MigrationStep
{
    private const INDEX = <<<'SQL'
CREATE INDEX `dt_desc.__TABLE__.__COL__` ON `__TABLE__` (`__COL__` desc);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635019152;
    }

    public function update(Connection $connection): void
    {
        $this->addDateTimeIndex($connection, 'heptaconnect_portal_node_storage', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_portal_node_storage', 'updated_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_portal_node_storage', 'expired_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function executeSql(Connection $connection, string $sql): void
    {
        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } else {
            $connection->exec($sql);
        }
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $this->executeSql($connection, \str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
