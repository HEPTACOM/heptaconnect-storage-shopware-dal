<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1674420000AddJobTransactionIdIndex extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE `heptaconnect_job` ADD INDEX `i.heptaconnect_job.transaction_id` (`transaction_id`);
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1674420000;
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
