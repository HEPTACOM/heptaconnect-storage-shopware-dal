<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1635019148MappingIndexHappenedAtColumns extends MigrationStep
{
    private const string INDEX = <<<'SQL'
CREATE INDEX `dt_desc.__TABLE__.__COL__` ON `__TABLE__` (`__COL__` desc);
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1635019148;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $this->addDateTimeIndex($connection, 'heptaconnect_mapping', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_mapping', 'updated_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_mapping', 'deleted_at');
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
