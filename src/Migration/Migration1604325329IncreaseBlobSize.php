<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1604325329IncreaseBlobSize extends MigrationStep
{
    public const string UP = <<<'SQL'
alter table heptaconnect_portal_node_storage modify value longblob not null;
SQL;

    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1604325329;
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
