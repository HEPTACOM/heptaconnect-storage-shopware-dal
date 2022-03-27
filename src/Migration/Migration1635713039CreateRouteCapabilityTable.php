<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635713039CreateRouteCapabilityTable extends MigrationStep
{
    private const UP = <<<'SQL'
CREATE TABLE `heptaconnect_route_capability` (
    `id` BINARY(16) NOT NULL,
    `name` VARCHAR(1020) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL DEFAULT NULL,
    `deleted_at` DATETIME(3) NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uniq.heptaconnect_route_capability.name` (`name`)
)
ENGINE=InnoDB
DEFAULT CHARSET='binary'
COLLATE='binary';
SQL;

    private const INDEX = <<<'SQL'
CREATE INDEX `dt_desc.__TABLE__.__COL__` ON `__TABLE__` (`__COL__` desc);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635713039;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
        $this->addDateTimeIndex($connection, 'heptaconnect_route_capability', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_route_capability', 'updated_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_route_capability', 'deleted_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
