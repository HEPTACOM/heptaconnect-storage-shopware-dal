<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Delete\JobDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final readonly class JobDelete implements JobDeleteActionInterface
{
    public const string DELETE_QUERY = 'f60b01fc-8f9a-4a37-a009-a00db9a64b11';

    public const string LOOKUP_QUERY = 'c1c41a80-6aec-4499-a07a-26ee57b07594';

    private const string DELETE_AFFECTED_JOBS_PAYLOAD = <<<'SQL'
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

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function delete(JobDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($jobKey));
            }

            $ids[] = Id::toBinary($jobKey->getUuid());
        }

        $selectBuilder = $this->queryFactory->createSelectBuilder(self::LOOKUP_QUERY);
        $selectBuilder
            ->from('heptaconnect_job', 'job')
            ->select('job.payload_id')
            ->distinct()
            ->where($selectBuilder->expr()->in('id', ':ids'))
            ->andWhere($selectBuilder->expr()->isNotNull('job.payload_id'));

        $deleteJobBuilder = $this->queryFactory->createBuilder(self::DELETE_QUERY);
        $deleteJobBuilder
            ->delete('heptaconnect_job')
            ->where($deleteJobBuilder->expr()->in('id', ':ids'));

        foreach (\array_chunk($ids, 1000) as $chunkedIds) {
            $chunkedPayloadIds = $selectBuilder
                ->setParameter('ids', $chunkedIds, ArrayParameterType::STRING)
                ->setMaxResults(\count($chunkedIds))
                ->iterateColumn('job.id');

            $payloadIds = \iterable_to_array($chunkedPayloadIds);

            $this->connection->transactional(function () use (
                $deleteJobBuilder,
                $chunkedIds,
                $payloadIds
            ): void {
                $deleteJobBuilder
                    ->setParameter('ids', $chunkedIds, ArrayParameterType::STRING)
                    ->executeStatement();

                if ($payloadIds !== []) {
                    $this->connection->executeStatement(
                        self::DELETE_AFFECTED_JOBS_PAYLOAD,
                        ['ids' => $payloadIds],
                        ['ids' => ArrayParameterType::STRING]
                    );
                }
            });
        }
    }
}
