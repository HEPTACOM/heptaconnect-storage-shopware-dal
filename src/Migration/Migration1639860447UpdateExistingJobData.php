<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639860447UpdateExistingJobData extends MigrationStep
{
    public const UP = <<<'SQL'
insert into heptaconnect_job_history
select
    unhex(md5(concat(job.id, 0xca5ef83ffd114913a81477efafa14272))) as id,
    job.id as job_id,
    0xca5ef83ffd114913a81477efafa14272 as state_id, # started
    'Migration' as message,
    job.started_at as created_at
from heptaconnect_job job
where state_id is null
  and started_at is not null;

insert into heptaconnect_job_history
select
    unhex(md5(concat(job.id, 0x6575ad837c71416f887d0e516a1bd813))) as id,
    job.id as job_id,
    0x6575ad837c71416f887d0e516a1bd813 as state_id, # finished
    'Migration' as message,
    job.finished_at as created_at
from heptaconnect_job job
where state_id is null
  and finished_at is not null;

update heptaconnect_job
set state_id = 0x6575ad837c71416f887d0e516a1bd813 # finished
where state_id is null
and finished_at is not null;

update heptaconnect_job
set state_id = 0xca5ef83ffd114913a81477efafa14272 # started
where state_id is null
and started_at is not null;

update heptaconnect_job
set state_id = 0x3aee495720734539b98f0605c33e59d2 # open
where state_id is null;

alter table heptaconnect_job
    modify state_id binary(16) not null;
SQL;

    public function getCreationTimestamp(): int
    {
        return 1639860447;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement(self::UP);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
