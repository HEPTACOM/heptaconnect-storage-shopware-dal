<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1663693295CreateUiAuditTrailDataTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_ui_audit_trail_data`
(
    `id`                binary(16)      NOT NULL,
    `ui_audit_trail_id` binary(16)      NOT NULL,
    `payload`           blob            NOT NULL,
    `format`            varbinary(256)  NOT NULL,
    `created_at`        datetime(3)     NOT NULL,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk.heptaconnect_ui_audit_trail_data.ui_audit_trail_id`
        FOREIGN KEY (`ui_audit_trail_id`)
        REFERENCES `heptaconnect_ui_audit_trail` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    INDEX `dt_desc.heptaconnect_ui_audit_trail_data.created_at` (`created_at` DESC),
    INDEX `i.heptaconnect_ui_audit_trail_data.format` (`format`)
)
    ENGINE=InnoDB
    DEFAULT charset = `binary`;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1663693295;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
