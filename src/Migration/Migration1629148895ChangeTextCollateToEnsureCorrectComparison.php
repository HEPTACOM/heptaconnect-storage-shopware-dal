<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1629148895ChangeTextCollateToEnsureCorrectComparison extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1629148895;
    }

    public function update(Connection $connection): void
    {
        $sqls = [
            $this->createIndexDropSql('heptaconnect_mapping', 'i.heptaconnect_mapping.external_id'),
            $this->createConversionSql('heptaconnect_mapping', 'external_id', 512 * 4, true),
            $this->createConversionTableSql('heptaconnect_mapping'),
            $this->createIndexCreateSql('heptaconnect_mapping', 'i.heptaconnect_mapping.external_id', 'external_id', false),

            $this->createIndexDropSql('heptaconnect_dataset_entity_type', 'uniq.heptaconnect_dataset_entity_type.type'),
            $this->createConversionSql('heptaconnect_dataset_entity_type', 'type', 255 * 4, false),
            $this->createConversionTableSql('heptaconnect_dataset_entity_type'),
            $this->createIndexCreateSql('heptaconnect_dataset_entity_type', 'uniq.heptaconnect_dataset_entity_type.type', 'type', true),

            $this->createConversionSql('heptaconnect_job', 'external_id', 512 * 4, true),
            $this->createConversionTableSql('heptaconnect_job'),

            $this->createConversionSql('heptaconnect_job_payload', 'format', 255 * 4, false),
            $this->createConversionSql('heptaconnect_job_payload', 'checksum', 255 * 4, false),
            $this->createConversionTableSql('heptaconnect_job_payload'),

            $this->createIndexDropSql('heptaconnect_job_type', 'uniq.heptaconnect_job_type.type'),
            $this->createConversionSql('heptaconnect_job_type', 'type', 255 * 4, false),
            $this->createConversionTableSql('heptaconnect_job_type'),
            $this->createIndexCreateSql('heptaconnect_job_type', 'uniq.heptaconnect_job_type.type', 'type', true),

            $this->createConversionSql('heptaconnect_portal_node', 'class_name', 255 * 4, false),
            $this->createConversionTableSql('heptaconnect_portal_node'),

            $this->createConversionSql('heptaconnect_portal_node_storage', 'key', 1024 * 4, false),
            $this->createConversionSql('heptaconnect_portal_node_storage', 'type', 255 * 4, false),
            $this->createConversionTableSql('heptaconnect_portal_node_storage'),
        ];
        $sql = \implode(\PHP_EOL, $sqls);

        // doctrine/dbal 2 support
        if (\method_exists($connection, 'executeStatement')) {
            $connection->executeStatement($sql);
        } else {
            $connection->exec($sql);
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    protected function createConversionSql(string $table, string $column, int $newSize, bool $nullable): string
    {
        $nullType = $nullable ? ' NULL' : '';

        return \sprintf('ALTER TABLE `%s` CHANGE `%s` `%s` VARCHAR(%s) %s;', $table, $column, $column, $newSize, $nullType);
    }

    protected function createConversionTableSql(string $table): string
    {
        return \sprintf('ALTER TABLE `%s` CONVERT TO CHARACTER SET \'binary\';', $table);
    }

    protected function createIndexDropSql(string $table, string $index): string
    {
        return \sprintf('DROP INDEX `%s` ON `%s`;', $index, $table);
    }

    protected function createIndexCreateSql(string $table, string $index, string $column, bool $unique): string
    {
        $uniqueType = $unique ? ' UNIQUE ' : ' ';

        return \sprintf('CREATE %s INDEX `%s` ON `%s`(`%s`);', $uniqueType, $index, $table, $column);
    }
}
