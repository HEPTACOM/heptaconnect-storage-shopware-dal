<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1632763825RenameDatasetEntityTypeTable extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE heptaconnect_dataset_entity_type RENAME TO heptaconnect_entity_type;
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1632763825;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }
}
