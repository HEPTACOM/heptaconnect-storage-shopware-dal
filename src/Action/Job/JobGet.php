<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final class JobGet implements JobGetActionInterface
{
    public const string FETCH_QUERY = '809ecd5e-291f-417c-9c76-003c7ead65e9';

    /**
     * @deprecated TODO remove serialized format
     */
    private const string FORMAT_SERIALIZED = 'serialized';

    /**
     * @deprecated TODO remove serialized format
     */
    private const string FORMAT_SERIALIZED_GZPRESS = 'serialized+gzpress';

    private ?SelectQueryBuilder $builder = null;

    public function __construct(
        private readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function get(JobGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($jobKey));
            }

            $ids[] = $jobKey->getUuid();
        }

        return $ids === [] ? [] : $this->yieldJobs($ids);
    }

    private function getBuilderCached(): SelectQueryBuilder
    {
        if (!$this->builder instanceof SelectQueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::FETCH_QUERY);

        return $builder
            ->from('heptaconnect_job', 'job')
            ->innerJoin(
                'job',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('entity_type.id', 'job.entity_type_id')
            )
            ->innerJoin(
                'job',
                'heptaconnect_job_type',
                'job_type',
                $builder->expr()->eq('job_type.id', 'job.job_type_id')
            )
            ->innerJoin(
                'job',
                'heptaconnect_portal_node',
                'portal_node',
                $builder->expr()->eq('portal_node.id', 'job.portal_node_id')
            )
            ->leftJoin(
                'job',
                'heptaconnect_job_payload',
                'job_payload',
                $builder->expr()->eq('job_payload.id', 'job.payload_id')
            )
            ->select([
                'job.id job_id',
                'job.external_id job_external_id',
                'job_type.type job_type_type',
                'entity_type.type job_entity_type',
                'portal_node.id portal_node_id',
                'job_payload.payload job_payload_payload',
                'job_payload.format job_payload_format',
            ])
            ->addOrderBy('job.id')
            ->where($builder->expr()->in('job.id', ':ids'));
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<JobGetResult>
     */
    private function yieldJobs(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Id::toBinaryList($ids), ArrayParameterType::STRING);

        /**
         * @var array{
         *     job_id: string,
         *     job_external_id: string,
         *     job_type_type: string,
         *     job_entity_type: string,
         *     portal_node_id: string,
         *     job_payload_payload: string|null,
         *     job_payload_format: string|null
         * } $row
         */
        foreach ($builder->iterateRows() as $row) {
            yield new JobGetResult(
                $row['job_type_type'],
                new JobStorageKey(Id::toHex($row['job_id'])),
                new MappingComponentStruct(
                    new PortalNodeStorageKey(Id::toHex($row['portal_node_id'])),
                    new EntityType($row['job_entity_type']),
                    $row['job_external_id']
                ),
                $this->unserializePayload($row['job_payload_payload'], (string) $row['job_payload_format'])
            );
        }
    }

    private function unserializePayload(?string $payload, string $format): ?array
    {
        if (!\is_string($payload)) {
            return null;
        }

        if ($format === self::FORMAT_SERIALIZED) {
            return (array) \unserialize($payload);
        }

        if ($format === self::FORMAT_SERIALIZED_GZPRESS) {
            return (array) \unserialize(\gzuncompress($payload));
        }

        return (array) $payload;
    }
}
