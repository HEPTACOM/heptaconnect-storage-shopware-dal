<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1677950300AddExternalIdIndexToJobTable extends MigrationStep
{
    private const INDEX = <<<'SQL'
CREATE INDEX `i.__TABLE__.__COL__` ON `__TABLE__` (`__COL__`);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1677950300;
    }

    public function update(Connection $connection): void
    {
        $this->addIndex($connection, 'heptaconnect_job', 'external_id');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function addIndex(Connection $connection, string $table, string $column): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
