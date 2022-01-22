<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642624782CreatePortalNodeConfigurationTable extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE `heptaconnect_portal_node`
    ADD COLUMN `configuration` JSON NULL AFTER `class_name`;

UPDATE
    `heptaconnect_portal_node`
SET
    `configuration` = '{}';

ALTER TABLE `heptaconnect_portal_node`
    MODIFY COLUMN `configuration` JSON NOT NULL;
SQL;

    public const DESTRUCTIVE = <<<'SQL'
DELETE FROM
    `system_config`
WHERE
    `configuration_key` LIKE 'heptacom.heptaConnect.portalNodeConfiguration%'
SQL;

    public function getCreationTimestamp(): int
    {
        return 1642624782;
    }

    public function update(Connection $connection): void
    {
        $this->executeSql($connection, self::UP);

        if ($connection->getSchemaManager()->tablesExist('system_config')) {
            $select = $connection->createQueryBuilder();
            $migrateableConfiguration = $select->from('system_config')
                ->select([
                    'configuration_key',
                    'configuration_value',
                ])
                ->where($select->expr()->like('configuration_key', ':pattern'))
                ->setParameter('pattern', 'heptacom.heptaConnect.portalNodeConfiguration%')
                ->execute()
                ->fetchAll();

            $update = $connection->createQueryBuilder();
            $update->update('heptaconnect_portal_node')
                ->set('configuration', ':config')
                ->where($update->expr()->eq('id', ':id'));

            foreach ($migrateableConfiguration as $row) {
                $configurationKey = (string) ($row['configuration_key'] ?? null);
                $configurationValue = (string) ($row['configuration_value'] ?? null);

                if ($configurationKey !== '' && $configurationValue !== '') {
                    $json = \json_decode($configurationValue, true);
                    $portalNodeKey = \mb_substr($configurationKey, \mb_strlen('heptacom.heptaConnect.portalNodeConfiguration'));
                    $portalNodeId = \hex2bin($portalNodeKey);

                    if (\is_array($json) && \is_string($portalNodeId)) {
                        $value = $json['_value'] ?? null;
                        $jsonedValue = \json_encode($update);

                        if ($value !== null && \is_string($jsonedValue)) {
                            $update
                                ->setParameter('id', $portalNodeId, Types::BINARY)
                                ->setParameter('config', $jsonedValue, Types::BINARY)
                                ->execute();
                        }
                    }
                }
            }
        }
        $this->executeSql($connection, self::DESTRUCTIVE);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function executeSql(Connection $connection, string $sql): void
    {
        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } else {
            $connection->exec($sql);
        }
    }
}
