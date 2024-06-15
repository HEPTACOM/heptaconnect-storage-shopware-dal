<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1677428200AddKeyIndexToPortalNodeStorageTable extends MigrationStep
{
    private const INDEX = <<<'SQL'
CREATE INDEX `i.__TABLE__.__COL__` ON `__TABLE__` (`__COL__` (__SIZE__));
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1677428200;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $this->addIndex($connection, 'heptaconnect_portal_node_storage', 'key', 3072);
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }

    private function addIndex(Connection $connection, string $table, string $column, int $size): void
    {
        $connection->executeStatement(\str_replace(['__TABLE__', '__COL__', '__SIZE__'], [$table, $column, $size], self::INDEX));
    }
}
