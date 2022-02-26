<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Delete\JobDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;

class JobDelete implements JobDeleteActionInterface
{
    private const DELETE_AFFECTED_JOBS_PAYLOAD = <<<'SQL'
DELETE
    job_payload
FROM
    heptaconnect_job_payload job_payload
LEFT JOIN
    heptaconnect_job job
ON
    job.payload_id = job_payload.id
WHERE
    job.id IS NULL
AND
    job_payload.id IN (:ids)
SQL;

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

            $ids[] = \hex2bin($jobKey->getUuid());
        }

        $selectBuilder = new QueryBuilder($this->connection);
        $payloadIds = $selectBuilder
            ->from('heptaconnect_job', 'job')
            ->addOrderBy('job.id')
            ->select('job.payload_id')
            ->where($selectBuilder->expr()->in('id', ':ids'))
            ->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY)
            ->setMaxResults(\count($ids))
            ->execute()
            ->fetchAll(\PDO::FETCH_COLUMN);

        $deleteJobBuilder = new QueryBuilder($this->connection);
        $deleteJobBuilder
            ->delete('heptaconnect_job')
            ->where($deleteJobBuilder->expr()->in('id', ':ids'))
            ->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY)
            ->execute();

        if ($payloadIds !== []) {
            $this->connection->executeStatement(self::DELETE_AFFECTED_JOBS_PAYLOAD, ['ids' => $payloadIds], ['ids' => Connection::PARAM_STR_ARRAY]);
        }
    }
}
