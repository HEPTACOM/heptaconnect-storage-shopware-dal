<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639246133CreateStateHistoryForJobs extends MigrationStep
{
    public const UP = <<<'SQL'
CREATE TABLE `heptaconnect_job_state` (
  `id` BINARY(16) NOT NULL,
  `name` VARBINARY(128) NOT NULL,
  `created_at` DATETIME(3) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq.heptaconnect_job_state.name` (`name`)
)
ENGINE=InnoDB
DEFAULT CHARSET='binary'
COLLATE='binary';

CREATE TABLE `heptaconnect_job_history` (
  `id` BINARY(16) NOT NULL,
  `job_id` BINARY(16) NOT NULL,
  `state_id` BINARY(16) DEFAULT NULL,
  `message` TEXT,
  `created_at` DATETIME(3) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk.heptaconnect_job_history.job_id` FOREIGN KEY (`job_id`) REFERENCES `heptaconnect_job` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk.heptaconnect_job_history.state_id` FOREIGN KEY (`state_id`) REFERENCES `heptaconnect_job_state` (`id`)
)
ENGINE=InnoDB
DEFAULT CHARSET='binary'
COLLATE='binary';

ALTER TABLE heptaconnect_job
    ADD state_id BINARY(16) NULL AFTER payload_id;

ALTER TABLE heptaconnect_job
    ADD transaction_id BINARY(16) NULL;

ALTER TABLE heptaconnect_job
    ADD CONSTRAINT `fk.heptaconnect_job.state_id`
        FOREIGN KEY (state_id) REFERENCES heptaconnect_job_state (id);
SQL;

    public function getCreationTimestamp(): int
    {
        return 1639246133;
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
