<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1641403938AddChecksumIndexToJobPayloadTable extends MigrationStep
{
    private const INDEX = <<<'SQL'
CREATE INDEX `i.__TABLE__.__COL__` ON `__TABLE__` (`__COL__`);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1641403938;
    }

    public function update(Connection $connection): void
    {
        $this->addIndex($connection, 'heptaconnect_job_payload', 'checksum');
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function executeSql(Connection $connection, string $sql): void
    {
        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } else {
            $connection->exec($sql);
        }
    }

    private function addIndex(Connection $connection, string $table, string $column): void
    {
        $this->executeSql($connection, \str_replace(['__TABLE__', '__COL__'], [$table, $column], self::INDEX));
    }
}
