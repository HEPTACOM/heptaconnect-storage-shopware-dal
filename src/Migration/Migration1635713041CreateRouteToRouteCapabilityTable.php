<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635713041CreateRouteToRouteCapabilityTable extends MigrationStep
{
    private const UP = <<<'SQL'
CREATE TABLE `heptaconnect_route_has_capability` (
    `route_id` BINARY(16) NOT NULL,
    `route_capability_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`route_id`, `route_capability_id`),
    UNIQUE INDEX `uniq.heptaconnect_route_has_capability.primary` (`route_id`, `route_capability_id`),
    FOREIGN KEY `fk.heptaconnect_route_has_capability.route_id` (`route_id`)
        REFERENCES `heptaconnect_route` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    FOREIGN KEY `fk.heptaconnect_route_has_capability.route_capability_id` (`route_capability_id`)
        REFERENCES `heptaconnect_route_capability` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
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
        return 1635713041;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
        $this->addDateTimeIndex($connection, 'heptaconnect_route_has_capability', 'created_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
