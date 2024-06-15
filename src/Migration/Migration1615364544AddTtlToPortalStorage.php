<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1615364544AddTtlToPortalStorage extends MigrationStep
{
    public const UP = <<<'SQL'
alter table heptaconnect_portal_node_storage add expired_at datetime(3) null;
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1615364544;
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
