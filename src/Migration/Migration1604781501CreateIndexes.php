<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1604781501CreateIndexes extends MigrationStep
{
    public const string UP = <<<'SQL'
alter table heptaconnect_mapping add index `i.heptaconnect_mapping.external_id` (`external_id`);
alter table enqueue add index `i.enqueue.delivery_id` (`delivery_id`);
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1604781501;
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
