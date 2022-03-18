<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

class JobGet implements JobGetActionInterface
{
    public const FETCH_QUERY = '809ecd5e-291f-417c-9c76-003c7ead65e9';

    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED = 'serialized';

    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED_GZPRESS = 'serialized+gzpress';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    private QueryIterator $iterator;

    public function __construct(QueryFactory $queryFactory, QueryIterator $iterator)
    {
        $this->queryFactory = $queryFactory;
        $this->iterator = $iterator;
    }

    public function get(JobGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getJobKeys() as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($jobKey));
            }

            $ids[] = $jobKey->getUuid();
        }

        return $ids === [] ? [] : $this->yieldJobs($ids);
    }

    protected function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);

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
    protected function yieldJobs(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Id::toBinaryList($ids), Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $this->iterator->iterate($builder),
            fn (array $row): JobGetResult => new JobGetResult(
                (string) $row['job_type_type'],
                new JobStorageKey(Id::toHex((string) $row['job_id'])),
                new MappingComponentStruct(
                    new PortalNodeStorageKey(Id::toHex((string) $row['portal_node_id'])),
                    (string) $row['job_entity_type'],
                    (string) $row['job_external_id']
                ),
                $this->unserializePayload($row['job_payload_payload'], (string) $row['job_payload_format'])
            )
        );
    }

    private function unserializePayload($payload, string $format): ?array
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
