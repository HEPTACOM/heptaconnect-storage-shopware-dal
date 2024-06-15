<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

final class Migration1642885343RemoveCronjobAndCronjobRunTable extends MigrationStep
{
    #[\Override]
    public function getCreationTimestamp(): int
    {
        return 1642885343;
    }

    #[\Override]
    public function update(Connection $connection): void
    {
        $connection->getSchemaManager()->dropTable('heptaconnect_cronjob_run');
        $connection->getSchemaManager()->dropTable('heptaconnect_cronjob');
    }

    #[\Override]
    public function updateDestructive(Connection $connection): void
    {
    }
}
