<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1663693296CreateUiAuditTrailErrorTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_ui_audit_trail_error`
(
    `id`                binary(16)       NOT NULL,
    `ui_audit_trail_id` binary(16)       NOT NULL,
    `code`              varbinary(128)   NOT NULL,
    `exception_class`   varbinary(1024)  NOT NULL,
    `depth`             integer(3)       NOT NULL,
    `message`           varbinary(1024)  NOT NULL,
    `logged_at`         datetime(3)      NOT NULL,
    `created_at`        datetime(3)      NOT NULL,

    PRIMARY KEY (`id`),
    CONSTRAINT `fk.heptaconnect_ui_audit_trail_error.ui_audit_trail_id`
        FOREIGN KEY (`ui_audit_trail_id`)
        REFERENCES `heptaconnect_ui_audit_trail` (`id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE,
    INDEX `dt_desc.heptaconnect_ui_audit_trail_error.created_at` (`created_at` DESC),
    INDEX `dt_desc.heptaconnect_ui_audit_trail_error.logged_at` (`logged_at` DESC),
    INDEX `i_asc.heptaconnect_ui_audit_trail_error.depth` (`depth` ASC),
    INDEX `i.heptaconnect_ui_audit_trail_error.code` (`code`),
    INDEX `i.heptaconnect_ui_audit_trail_error.exception_class` (`exception_class`)
)
    ENGINE=InnoDB
    DEFAULT charset = `binary`;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1663693296;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
