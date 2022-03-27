<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1636817109CreateWebHttpHandlerTable extends MigrationStep
{
    private const UP = <<<'SQL'
CREATE TABLE `heptaconnect_web_http_handler` (
    `id` BINARY(16) NOT NULL,
    `path_id` BINARY(16) NOT NULL,
    `portal_node_id` BINARY(16) NOT NULL,
    `created_at` DATETIME(3) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `uniq.heptaconnect_web_http_handler.path_id_portal_node_id` (`path_id`, `portal_node_id`),
    FOREIGN KEY `fk.heptaconnect_web_http_handler.path_id` (`path_id`)
        REFERENCES `heptaconnect_web_http_handler_path` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    FOREIGN KEY `fk.heptaconnect_web_http_handler.portal_node_id` (`portal_node_id`)
        REFERENCES `heptaconnect_portal_node` (`id`)
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
        return 1636817109;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
        $this->addDateTimeIndex($connection, 'heptaconnect_web_http_handler', 'created_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
