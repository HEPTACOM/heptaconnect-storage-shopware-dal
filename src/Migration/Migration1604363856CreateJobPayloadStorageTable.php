<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604363856CreateJobPayloadStorageTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE IF NOT EXISTS `heptaconnect_job_payload` (
    `id` BINARY(16) NOT NULL,
    `payload` LONGBLOB NOT NULL,
    `format` VARCHAR(255) NOT NULL,
    `checksum` VARCHAR(255) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1604363856;
    }

    public function update(Connection $connection): void
    {
        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement(self::UP);
        } else {
            $connection->exec(self::UP);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
