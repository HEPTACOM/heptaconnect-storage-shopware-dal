<?php

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1632763825RenameDatasetEntityTypeTable extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE heptaconnect_dataset_entity_type RENAME TO heptaconnect_entity_type;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1632763825;
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
