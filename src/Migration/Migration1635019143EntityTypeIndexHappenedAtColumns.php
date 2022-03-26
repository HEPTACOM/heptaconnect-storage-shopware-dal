<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1635019143EntityTypeIndexHappenedAtColumns extends MigrationStep
{
    private const INDEX = <<<'SQL'
CREATE INDEX `dt_desc.__TABLE__.__COL__` ON `__TABLE__` (`__COL__` desc);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635019143;
    }

    public function update(Connection $connection): void
    {
        $this->addDateTimeIndex($connection, 'heptaconnect_entity_type', 'created_at');
        $this->addDateTimeIndex($connection, 'heptaconnect_entity_type', 'updated_at');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addDateTimeIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
