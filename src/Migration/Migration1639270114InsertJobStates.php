<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639270114InsertJobStates extends MigrationStep
{
    public const UP = <<<'SQL'
insert into heptaconnect_job_state (id, name, created_at)
values (0x3aee495720734539b98f0605c33e59d2, 'open', NOW());

insert into heptaconnect_job_state (id, name, created_at)
values (0xca5ef83ffd114913a81477efafa14272, 'started', NOW());

insert into heptaconnect_job_state (id, name, created_at)
values (0x28ff3666f0f746cf84770c1148600605, 'failed', NOW());

insert into heptaconnect_job_state (id, name, created_at)
values (0x6575ad837c71416f887d0e516a1bd813, 'finished', NOW());
SQL;

    public function getCreationTimestamp(): int
    {
        return 1639270114;
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
