<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeCollection;
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
use Shopware\Core\Framework\Uuid\Uuid;

class JobRepository extends JobRepositoryContract
{
    private EntityRepositoryInterface $jobs;

    private EntityRepositoryInterface $jobTypes;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ContextFactory $contextFactory;

    private DatasetEntityTypeAccessor $datasetEntityTypeAccessor;

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

    public function add(
        MappingComponentStructContract $mapping,
        string $jobType,
        ?JobPayloadKeyInterface $jobPayloadKey
    ): JobKeyInterface {
        $key = $this->storageKeyGenerator->generateKey(JobKeyInterface::class);

        if (!$key instanceof JobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $portalNodeKey = $mapping->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        if ($jobPayloadKey !== null && !$jobPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($jobPayloadKey));
        }

        $context = $this->contextFactory->create();
        $datasetEntityClassName = $mapping->getDatasetEntityClassName();

        $this->jobs->create([[
            'id' => $key->getUuid(),
            'externalId' => $mapping->getExternalId(),
            'portalNodeId' => $portalNodeKey->getUuid(),
            'entityTypeId' => $this->datasetEntityTypeAccessor->getIdsForTypes([$datasetEntityClassName], $context)[$datasetEntityClassName],
            'jobTypeId' => $this->getIdsForJobType([$jobType], $context)[$jobType],
            'payloadId' => $jobPayloadKey === null ? null : $jobPayloadKey->getUuid(),
        ]], $context);

        return $key;
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

    /**
     * @psalm-param array<array-key, string> $types
     * @psalm-return array<string, string>
     */
    private function getIdsForJobType(array $types, Context $context): array
    {
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
                $typeIds[$typeName] = $id;
            }
        }

        if (\count($insert) > 0) {
            $this->jobTypes->create($insert, $context);
        }

        return $typeIds;
    }
}
