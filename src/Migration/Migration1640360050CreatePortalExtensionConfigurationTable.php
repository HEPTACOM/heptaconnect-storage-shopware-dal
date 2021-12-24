<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1640360050CreatePortalExtensionConfigurationTable extends MigrationStep
{
    public const UP = <<<'SQL'
create table heptaconnect_portal_node_extension
(
    id             binary(16)      not null,
    portal_node_id binary(16)      not null,
    class_name     varbinary(1020) not null,
    active         bool            not null,
    created_at     datetime(3)     not null,
    constraint heptaconnect_portal_node_extension_pk
        primary key (id),
    constraint `fk.heptaconnect_portal_node_extension.portal_node_id`
        foreign key (portal_node_id) references heptaconnect_portal_node (id)
            on update cascade on delete cascade
) charset = `binary`;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1640360050;
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
