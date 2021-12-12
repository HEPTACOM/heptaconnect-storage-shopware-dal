<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639260076AddTransactionIdToJobTable extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE heptaconnect_job ADD transaction_id BINARY(16) NULL;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1639260076;
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
