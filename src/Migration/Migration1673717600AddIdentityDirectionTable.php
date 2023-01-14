<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1673717600AddIdentityDirectionTable extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_identity_direction`
(
    `id`                 BINARY(16)      NOT NULL,
    `type_id`            BINARY(16)      NOT NULL,
    `source_portal_node` BINARY(16)      NOT NULL,
    `target_portal_node` BINARY(16)      NOT NULL,
    `source_external_id` VARBINARY(1024) NOT NULL,
    `target_external_id` VARBINARY(1024) NULL,
    `created_at`         DATETIME(3)     NOT NULL,
    `updated_at`         DATETIME(3)     NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `u.heptaconnect_identity_direction.unique_source` (`source_portal_node`, `type_id`, `source_external_id`),
    INDEX `dt_desc.heptaconnect_identity_direction.created_at` (`created_at` DESC),
    INDEX `dt_desc.heptaconnect_identity_direction.updated_at` (`updated_at` DESC),
    CONSTRAINT `fk.heptaconnect_identity_direction.source_portal_node`
        FOREIGN KEY (`source_portal_node`) REFERENCES `heptaconnect_portal_node` (`id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
    CONSTRAINT `fk.heptaconnect_identity_direction.target_portal_node`
        FOREIGN KEY (`target_portal_node`) REFERENCES `heptaconnect_portal_node` (`id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE,
    CONSTRAINT `fk.heptaconnect_identity_direction.type_id`
        FOREIGN KEY (`type_id`) REFERENCES `heptaconnect_entity_type` (`id`)
            ON UPDATE CASCADE
            ON DELETE CASCADE
)
    ENGINE=InnoDB
    DEFAULT CHARSET='binary'
    COLLATE='binary';
SQL;

    public function getCreationTimestamp(): int
    {
        return 1673717600;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
