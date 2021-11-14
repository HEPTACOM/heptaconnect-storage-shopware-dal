<?php

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1636817110CreateWebHttpHandlerConfigurationTable extends MigrationStep
{
    private const UP = <<<'SQL'
CREATE TABLE `heptaconnect_web_http_handler_configuration` (
    `id` BINARY(16) NOT NULL,
    `handler_id` BINARY(16) NOT NULL,
    `key` VARCHAR(1020) NOT NULL,
    `value` LONGTEXT NOT NULL,
    `type` VARCHAR(512) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uniq.heptaconnect_web_http_handler_configuration.handler_id_key` (`handler_id`, `key`),
    INDEX `i.heptaconnect_web_http_handler_configuration.key` (`key`),
    FOREIGN KEY `fk.heptaconnect_web_http_handler_configuration.handler_id` (`handler_id`)
        REFERENCES `heptaconnect_web_http_handler` (`id`)
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
        return 1636817110;
    }

    public function update(Connection $connection): void
    {
        $this->executeSql($connection, self::UP);
        $this->addDateTimeIndex($connection, 'heptaconnect_web_http_handler_configuration', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_web_http_handler_configuration', 'updated_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_web_http_handler_configuration', 'deleted_at');
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
