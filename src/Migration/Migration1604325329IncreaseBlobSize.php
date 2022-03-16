<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1604325329IncreaseBlobSize extends MigrationStep
{
    public const UP = <<<'SQL'
alter table heptaconnect_portal_node_storage modify value longblob not null;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1604325329;
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
