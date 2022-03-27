<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604781501CreateIndexes extends MigrationStep
{
    public const UP = <<<'SQL'
alter table heptaconnect_mapping add index `i.heptaconnect_mapping.external_id` (`external_id`);
alter table enqueue add index `i.enqueue.delivery_id` (`delivery_id`);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1604781501;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
