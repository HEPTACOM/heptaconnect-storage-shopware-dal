<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1629643769AddJobStartAndFinishFields extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE heptaconnect_job ADD started_at DATETIME(3) NULL, ADD finished_at DATETIME(3) NULL;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1629643769;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
