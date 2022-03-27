<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604429812CreateJobTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_job_type`
(
	`id` BINARY(16) NOT NULL PRIMARY KEY,
	`type` VARCHAR(255) NOT NULL,
	`created_at` DATETIME(3) NOT NULL,
	`updated_at` DATETIME(3) NULL,
	CONSTRAINT `uniq.heptaconnect_job_type.type`
		UNIQUE (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `heptaconnect_job`
(
	`id` BINARY(16) NOT NULL PRIMARY KEY,
	`external_id` VARCHAR(512) NOT NULL,
	`portal_node_id` BINARY(16) NOT NULL,
	`entity_type_id` BINARY(16) NOT NULL,
	`job_type_id` BINARY(16) NOT NULL,
	`payload_id` BINARY(16) NULL,
	`created_at` DATETIME(3) NOT NULL,
	`updated_at` DATETIME(3) NULL,
	CONSTRAINT `fk.heptaconnect_job.entity_type_id`
		FOREIGN KEY (`entity_type_id`) REFERENCES `heptaconnect_dataset_entity_type` (`id`),
	CONSTRAINT `fk.heptaconnect_job.job_type_id`
		FOREIGN KEY (`job_type_id`) REFERENCES `heptaconnect_job_type` (`id`),
	CONSTRAINT `fk.heptaconnect_job.portal_node_id`
		FOREIGN KEY (`portal_node_id`) REFERENCES `heptaconnect_portal_node` (`id`),
	CONSTRAINT `fk.heptaconnect_job.payload_id`
		FOREIGN KEY (`payload_id`) REFERENCES `heptaconnect_job_payload` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1604429812;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
