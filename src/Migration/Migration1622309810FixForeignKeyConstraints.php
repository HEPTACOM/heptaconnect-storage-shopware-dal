<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1622309810FixForeignKeyConstraints extends MigrationStep
{
    public const UP = <<<'SQL'
DROP TABLE `heptaconnect_cronjob_run`;

DROP TABLE `heptaconnect_cronjob`;

DROP TABLE `heptaconnect_mapping_error_message`;

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
    CONSTRAINT `fk.heptaconnect_mapping_error_message.mapping_id` FOREIGN KEY (`mapping_id`)
        REFERENCES `heptaconnect_mapping` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk.heptaconnect_mapping_error_message.group_previous_id` FOREIGN KEY (`group_previous_id`)
        REFERENCES `heptaconnect_mapping_error_message` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    CONSTRAINT `fk.heptaconnect_mapping_error_message.previous_id` FOREIGN KEY (`previous_id`)
        REFERENCES `heptaconnect_mapping_error_message` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `heptaconnect_cronjob` (
    `id` BINARY(16) NOT NULL,
    `cron_expression` VARCHAR(255) NOT NULL,
    `portal_node_id` BINARY(16) NOT NULL,
    `handler` VARCHAR(255) NOT NULL,
    `payload` JSON NULL,
    `queued_until` DATETIME(3) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.heptaconnect_cronjob.portal_node_id` FOREIGN KEY (`portal_node_id`)
        REFERENCES `heptaconnect_portal_node` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE,
    CONSTRAINT `json.heptaconnect_cronjob.payload` CHECK (JSON_VALID(`payload`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `heptaconnect_cronjob_run` (
    `id` BINARY(16) NOT NULL,
    `cronjob_id` BINARY(16) NOT NULL,
    `copy_from_id` BINARY(16) NULL,
    `portal_node_id` BINARY(16) NOT NULL,
    `handler` VARCHAR(255) NOT NULL,
    `payload` JSON NULL,
    `throwable_class` VARCHAR(255) NULL,
    `throwable_message` MEDIUMTEXT NULL,
    `throwable_serialized` LONGTEXT NULL,
    `throwable_file` VARCHAR(1024) NULL,
    `throwable_line` INTEGER(10) NULL,
    `queued_for` DATETIME(3) NOT NULL,
    `started_at` DATETIME(3) NULL,
    `finished_at` DATETIME(3) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `json.heptaconnect_cronjob_run.payload` CHECK (JSON_VALID(`payload`)),
    CONSTRAINT `fk.heptaconnect_cronjob_run.cronjob_id` FOREIGN KEY (`cronjob_id`)
        REFERENCES `heptaconnect_cronjob` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
    CONSTRAINT `fk.heptaconnect_cronjob_run.copy_from_id` FOREIGN KEY (`copy_from_id`)
        REFERENCES `heptaconnect_cronjob_run` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
    CONSTRAINT `fk.heptaconnect_cronjob_run.portal_node_id` FOREIGN KEY (`portal_node_id`)
        REFERENCES `heptaconnect_portal_node` (`id`)
            ON DELETE RESTRICT
            ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1622309810;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
