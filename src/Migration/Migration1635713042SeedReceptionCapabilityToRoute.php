<?php

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1635713042SeedReceptionCapabilityToRoute extends MigrationStep
{
    private const UP = <<<'SQL'
INSERT INTO `heptaconnect_route_has_capability` (
    `route_id`,
    `route_capability_id`,
    `created_at`
) SELECT
    r.`id`,
    c.`id`,
    NOW()
FROM `heptaconnect_route` r
INNER JOIN `heptaconnect_route_capability` c
SQL;

    public function getCreationTimestamp(): int
    {
        return 1635713042;
    }

    public function update(Connection $connection): void
    {
        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement(self::UP, ['id' => Uuid::randomBytes()], ['id' => Type::BINARY]);
        } else {
            $connection->exec(self::UP);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
