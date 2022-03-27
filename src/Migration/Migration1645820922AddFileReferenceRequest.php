<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1645820922AddFileReferenceRequest extends MigrationStep
{
    public const UP = <<<'SQL'
create table heptaconnect_file_reference_request
(
    id                 binary(16)  not null primary key,
    portal_node_id     binary(16)  not null,
    serialized_request blob        not null,
    created_at         datetime(3) not null,
    updated_at         datetime(3) null,
    deleted_at         datetime(3) null,
    constraint `fk.heptaconnect_file_reference_request.portal_node_id`
        foreign key (portal_node_id) references heptaconnect_portal_node (id)
            on update cascade on delete cascade
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
        return 1645820922;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
        $this->addDateTimeIndex($connection, 'heptaconnect_file_reference_request', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_file_reference_request', 'updated_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
