<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Delete\JobDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Delete\JobDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

class JobDelete implements JobDeleteActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function delete(JobDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($jobKey));
            }

            $ids[] = Uuid::fromHexToBytes($jobKey->getUuid());
        }

        $builder = new QueryBuilder($this->connection);
        $builder
            ->delete('heptaconnect_job')
            ->where($builder->expr()->in('id', ':ids'))
            ->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY)
            ->execute();
        // TODO lock payload check and delete unused payloads
    }
}
