<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1643220550CreatePortalNodeAliasColumn extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE `heptaconnect_portal_node`
    ADD COLUMN `alias`
        VARCHAR(512)
        UNIQUE
        NULL
        COLLATE 'binary'
        AFTER `id`;
SQL;

    public const REVERSE_UP = <<<'SQL'
ALTER TABLE `heptaconnect_portal_node`
    DROP COLUMN `alias`;
SQL;

    public const DESTRUCTIVE = <<<'SQL'
DROP TABLE heptaconnect_bridge_key_alias
SQL;

    public function getCreationTimestamp(): int
    {
        return 1643220550;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);

        if (!$connection->getSchemaManager()->tablesExist('heptaconnect_bridge_key_alias')) {
            return;
        }

        try {
            $this->migrateAlias($connection);
        } catch (\Throwable $throwable) {
            $connection->executeStatement(self::REVERSE_UP);

            throw $throwable;
        }

        $connection->executeStatement(self::DESTRUCTIVE);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function migrateAlias(Connection $connection): void
    {
        $queryBuilderSelect = $connection->createQueryBuilder();
        $migrateableAlias = $queryBuilderSelect
            ->select([
                'original',
                'alias',
            ])
            ->from('heptaconnect_bridge_key_alias')
            ->where($queryBuilderSelect->expr()->like('original', ':prefix'))
            ->setParameter(':prefix', 'PortalNode:%')
            ->execute()
            ->fetchAllAssociative();

        $queryBuilderUpdate = $connection->createQueryBuilder();
        $queryBuilderUpdate->update('heptaconnect_portal_node')
            ->set('alias', ':alias')
            ->where($queryBuilderUpdate->expr()->eq('id', ':id'));

        foreach ($migrateableAlias as $row) {
            $original = (string) ($row['original'] ?? null);
            $alias = (string) ($row['alias'] ?? null);

            $key = \explode(':', $original, 2)[1];
            $portalNodeId = \hex2bin($key);

            $queryBuilderUpdate
                ->setParameter('id', $portalNodeId, Types::BINARY)
                ->setParameter('alias', $alias, Types::BINARY)
                ->execute();
        }
    }
}
