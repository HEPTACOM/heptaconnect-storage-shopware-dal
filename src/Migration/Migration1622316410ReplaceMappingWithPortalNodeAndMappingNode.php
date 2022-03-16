<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1622316410ReplaceMappingWithPortalNodeAndMappingNode extends MigrationStep
{
    public const UP = <<<'SQL'
alter table heptaconnect_mapping_error_message drop foreign key `fk.heptaconnect_mapping_error_message.mapping_id`;

alter table heptaconnect_mapping_error_message drop column mapping_id;

alter table heptaconnect_mapping_error_message
	add portal_node_id binary(16) null after id;

alter table heptaconnect_mapping_error_message
	add mapping_node_id binary(16) null after portal_node_id;

alter table heptaconnect_mapping_error_message
	add constraint heptaconnect_mapping_error_message_mapping_node_fk
		foreign key (mapping_node_id) references heptaconnect_mapping_node (id)
			on update cascade on delete cascade;

alter table heptaconnect_mapping_error_message
	add constraint heptaconnect_mapping_error_message_portal_node_fk
		foreign key (portal_node_id) references heptaconnect_portal_node (id)
			on update cascade on delete cascade;

SQL;

    public function getCreationTimestamp(): int
    {
        return 1622316410;
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
