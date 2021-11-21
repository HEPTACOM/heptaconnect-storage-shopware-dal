<?php

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635512814OnDeleteCascadeFromMappingNodeToMapping extends MigrationStep
{
    private const UP = <<<'SQL'
alter table heptaconnect_mapping drop foreign key `fk.heptaconnect_mapping.mapping_node_id`;

alter table heptaconnect_mapping
    add constraint `fk.heptaconnect_mapping.mapping_node_id`
        foreign key (mapping_node_id) references heptaconnect_mapping_node (id)
            on update cascade on delete cascade;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635512814;
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