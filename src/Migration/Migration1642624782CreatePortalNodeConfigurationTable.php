<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1642624782CreatePortalNodeConfigurationTable extends MigrationStep
{
    public const UP = <<<'SQL'
ALTER TABLE `heptaconnect_portal_node`
    ADD COLUMN `configuration`
        LONGTEXT
        NULL
        COLLATE 'binary'
        AFTER `class_name`;

UPDATE
    `heptaconnect_portal_node`
SET
    `configuration` = '{}';

ALTER TABLE `heptaconnect_portal_node`
    MODIFY COLUMN `configuration`
        LONGTEXT
        NOT NULL
        COLLATE 'binary';
SQL;

    public const REVERSE_UP = <<<'SQL'
ALTER TABLE `heptaconnect_portal_node`
    DROP COLUMN `configuration`;
SQL;

    public const DESTRUCTIVE = <<<'SQL'
DELETE FROM
    `system_config`
WHERE
    `configuration_key` LIKE 'heptacom.heptaConnect.portalNodeConfiguration.%'
SQL;

    public function getCreationTimestamp(): int
    {
        return 1642624782;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);

        try {
            $this->migrateConfiguration($connection);
        } catch (\Throwable $throwable) {
            $connection->executeStatement(self::REVERSE_UP);

            throw $throwable;
        }

        $connection->executeStatement(self::DESTRUCTIVE);
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function migrateConfiguration(Connection $connection): void
    {
        $select = $connection->createQueryBuilder();
        $migrateableConfiguration = $select->from('system_config')
            ->select([
                'configuration_key',
                'configuration_value',
            ])
            ->where($select->expr()->like('configuration_key', ':pattern'))
            ->setParameter('pattern', 'heptacom.heptaConnect.portalNodeConfiguration.%')
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
                try {
                    $json = \json_decode($configurationValue, true, 512, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new \RuntimeException('Cannot read and process JSON in configuration', 1642937283, $e);
                }

                $portalNodeKey = \mb_substr($configurationKey, \mb_strlen('heptacom.heptaConnect.portalNodeConfiguration.'));
                $portalNodeId = Id::toBinary($portalNodeKey);

                if (!\is_array($json) || !\is_string($portalNodeId)) {
                    throw new \RuntimeException('Cannot update configuration', 1642937284);
                }

                $value = $json['_value'] ?? null;
                $jsonedValue = \json_encode($value, \JSON_THROW_ON_ERROR);

                if ($value === null || !\is_string($jsonedValue)) {
                    throw new \RuntimeException('Cannot write processed JSON in configuration', 1642937285);
                }

                $update
                    ->setParameter('id', $portalNodeId, Types::BINARY)
                    ->setParameter('config', $jsonedValue, Types::BINARY)
                    ->execute();
            }
        }
    }
}
