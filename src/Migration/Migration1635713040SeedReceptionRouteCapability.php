<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1635713040SeedReceptionRouteCapability extends MigrationStep
{
    private const UP = <<<'SQL'
INSERT INTO `heptaconnect_route_capability` (
    `id`,
    `name`,
    `created_at`
) VALUES (
    :id,
    'reception',
    NOW()
);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635713040;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP, ['id' => Uuid::randomBytes()], ['id' => Types::BINARY]);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
