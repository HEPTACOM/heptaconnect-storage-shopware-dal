<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1590250578CreateErrorMessageTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_mapping_error_message` (
    `id` BINARY(16) NOT NULL,
    `mapping_id` BINARY(16) NOT NULL,
    `group_previous_id` BINARY(16) NULL,
    `previous_id` BINARY(16) NULL,
    `message` LONGTEXT NULL,
    `type` VARCHAR(255) NOT NULL,
    `stack_trace` LONGTEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY `fk.heptaconnect_mapping_error_message.mapping_id` (`mapping_id`)
        REFERENCES `heptaconnect_mapping` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    FOREIGN KEY `fk.heptaconnect_mapping_error_message.group_previous_id` (`group_previous_id`)
        REFERENCES `heptaconnect_mapping_error_message` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    FOREIGN KEY `fk.heptaconnect_mapping_error_message.previous_id` (`previous_id`)
        REFERENCES `heptaconnect_mapping_error_message` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1590250578;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
