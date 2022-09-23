<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1663693294CreateUiAuditTrailTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_ui_audit_trail`
(
    `id`               binary(16)      NOT NULL,
    `ui_type`          varbinary(1024) NOT NULL,
    `ui_action_type`   varbinary(1024) NOT NULL,
    `ui_identifier`    varbinary(1024) NOT NULL,
    `user_identifier`  varbinary(1024) NOT NULL,
    `arguments`        blob            NOT NULL,
    `arguments_format` varbinary(256)  NOT NULL,
    `created_at`       datetime(3)     NOT NULL,
    `started_at`       datetime(3)     NOT NULL,
    `finished_at`      datetime(3)     NULL,
    PRIMARY KEY (`id`),
    INDEX `i.heptaconnect_ui_audit_trail.ui_type` (`ui_type`),
    INDEX `i.heptaconnect_ui_audit_trail.ui_action_type` (`ui_action_type`),
    INDEX `i.heptaconnect_ui_audit_trail.ui_identifier` (`ui_identifier`),
    INDEX `i.heptaconnect_ui_audit_trail.user_identifier` (`user_identifier`),
    INDEX `i.heptaconnect_ui_audit_trail.arguments_format` (`arguments_format`)
    INDEX `dt_desc.heptaconnect_ui_audit_trail.created_at` (`created_at` DESC),
    INDEX `dt_desc.heptaconnect_ui_audit_trail.started_at` (`started_at` DESC),
    INDEX `dt_desc.heptaconnect_ui_audit_trail.finished_at` (`finished_at` DESC)
)
    ENGINE=InnoDB
    DEFAULT charset = `binary`;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1663693294;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
