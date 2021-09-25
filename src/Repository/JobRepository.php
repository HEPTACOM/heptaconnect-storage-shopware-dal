<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\Repository\JobAdd;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Job;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class JobRepository extends JobRepositoryContract
{
    private EntityRepositoryInterface $jobs;

    private EntityRepositoryInterface $jobTypes;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ContextFactory $contextFactory;

    private DatasetEntityTypeAccessor $datasetEntityTypeAccessor;

    private array $cachedJobTypes = [];

    public function __construct(
        EntityRepositoryInterface $jobs,
        EntityRepositoryInterface $jobTypes,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ContextFactory $contextFactory,
        DatasetEntityTypeAccessor $datasetEntityTypeAccessor
    ) {
        $this->jobs = $jobs;
        $this->jobTypes = $jobTypes;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->contextFactory = $contextFactory;
        $this->datasetEntityTypeAccessor = $datasetEntityTypeAccessor;
    }

    public function add(array $jobAdds): array
    {
        $result = [];
        $keys = \iterable_to_array($this->storageKeyGenerator->generateKeys(JobKeyInterface::class, \count($jobAdds)));

        $context = $this->contextFactory->create();

        $creates = [];

        /** @var JobAdd $jobAdd */
        foreach ($jobAdds as $jobAddKey => $jobAdd) {
            $key = \array_shift($keys);

            if (!$key instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $portalNodeKey = $jobAdd->getMapping()->getPortalNodeKey();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $jobPayloadKey = $jobAdd->getPayloadKey();

            if ($jobPayloadKey !== null && !$jobPayloadKey instanceof JobPayloadStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($jobPayloadKey));
            }

            $datasetEntityClassName = $jobAdd->getMapping()->getDatasetEntityClassName();

            $creates[] = [
                'id' => $key->getUuid(),
                'externalId' => $jobAdd->getMapping()->getExternalId(),
                'portalNodeId' => $portalNodeKey->getUuid(),
                'entityTypeId' => $this->datasetEntityTypeAccessor->getIdsForTypes([$datasetEntityClassName], $context)[$datasetEntityClassName],
                // TODO batch lookup
                'jobTypeId' => $this->getIdsForJobType([$jobAdd->getJobType()], $context)[$jobAdd->getJobType()],
                'payloadId' => $jobPayloadKey === null ? null : $jobPayloadKey->getUuid(),
            ];

            $result[$jobAddKey] = $key;
        }

        if ($creates !== []) {
            $this->jobs->create($creates, $context);
        }

        return $result;
    }

    public function remove(JobKeyInterface $jobKey): void
    {
        if (!$jobKey instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$jobKey->getUuid()]);
        $criteria->setLimit(1);
        $searchResult = $this->jobs->searchIds($criteria, $context);
        $storageId = $searchResult->firstId();

        if (\is_null($storageId)) {
            throw new NotFoundException();
        }

        $this->jobs->delete([[
            'id' => $storageId,
        ]], $context);
    }

    public function list(): iterable
    {
        $context = $this->contextFactory->create();
        $criteria = (new Criteria())->setLimit(50);
        $iterator = new RepositoryIterator($this->jobs, $context, $criteria);

        while (!\is_null($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new JobStorageKey($id);
            }
        }
    }

    public function has(JobKeyInterface $jobKey): bool
    {
        if (!$jobKey instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$jobKey->getUuid()]);
        $criteria->setLimit(1);
        $searchResult = $this->jobs->searchIds($criteria, $context);
        $storageId = $searchResult->firstId();

        return !\is_null($storageId);
    }

    public function get(JobKeyInterface $jobKey): JobInterface
    {
        if (!$jobKey instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$jobKey->getUuid()]);
        $criteria->addAssociations([
            'jobType',
            'entityType',
        ]);
        $criteria->setLimit(1);
        $entity = $this->jobs->search($criteria, $context)->first();

        if (!$entity instanceof JobEntity) {
            throw new NotFoundException();
        }

        return new Job(
            new MappingComponentStruct(
                new PortalNodeStorageKey($entity->getPortalNodeId()),
                $entity->getEntityType()->getType(),
                $entity->getExternalId()
            ),
            $entity->getJobType()->getType(),
            $entity->getPayloadId() === null ? null : new JobPayloadStorageKey($entity->getPayloadId())
        );
    }

    public function start(JobKeyInterface $jobKey, ?\DateTime $time): void
    {
        if (!$jobKey instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobKey));
        }
        $timeData[] = [
            'id' => $jobKey->getUuid(),
            'startedAt' => isset($time) ? $time : \date_create(),
        ];
        $this->jobs->update($timeData, $this->contextFactory->create());
    }

    public function finish(JobKeyInterface $jobKey, ?\DateTime $time): void
    {
        if (!$jobKey instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobKey));
        }
        $timeData[] = [
            'id' => $jobKey->getUuid(),
            'finishedAt' => isset($time) ? $time : \date_create(),
        ];
        $this->jobs->update($timeData, $this->contextFactory->create());
    }

    public function cleanup(): void
    {
        $context = $this->contextFactory->create();

        $criteria = (new Criteria())->addFilter(
            new NotFilter(NotFilter::CONNECTION_AND, [new EqualsFilter('finishedAt', null)])
        );

        $iterator = new RepositoryIterator($this->jobs, $context, $criteria);

        while (($finishedIds = $iterator->fetchIds()) !== null) {
            $this->jobs->delete(\array_map(
                static fn (string $finishedId): array => ['id' => $finishedId],
                $finishedIds
            ), $context);

            $criteria->setOffset(0);
        }
    }

    /**
     * @psalm-param array<array-key, string> $types
     * @psalm-return array<string, string>
     */
    private function getIdsForJobType(array $types, Context $context): array
    {
        $result = [];
        $typesToCacheCheck = $types;
        $types = [];

        foreach ($typesToCacheCheck as $type) {
            if (\array_key_exists($type, $this->cachedJobTypes)) {
                $result[$type] = $this->cachedJobTypes[$type];
            } else {
                $types[] = $type;
            }
        }

        if ($types !== []) {
            $datasetEntityCriteria = new Criteria();
            $datasetEntityCriteria->addFilter(new EqualsAnyFilter('type', $types));
            /** @var JobTypeCollection $jobTypes */
            $jobTypes = $this->jobTypes->search($datasetEntityCriteria, $context)->getEntities();
            $typeIds = $jobTypes->groupByType();
            $insert = [];

            foreach ($types as $typeName) {
                if (!\array_key_exists($typeName, $typeIds)) {
                    $id = Uuid::randomHex();
                    $insert[] = [
                        'id' => $id,
                        'type' => $typeName,
                    ];
                    $result[$typeName] = $id;
                    $this->cachedJobTypes[$typeName] = $id;
                } else {
                    $result[$typeName] = $typeIds[$typeName];
                    $this->cachedJobTypes[$typeName] = $typeIds[$typeName];
                }
            }

            if (\count($insert) > 0) {
                $this->jobTypes->create($insert, $context);
            }
        }

        return $result;
    }
}
