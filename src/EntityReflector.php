<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class EntityReflector extends EntityReflectorContract
{
    private EntityRepositoryInterface $mappingRepository;

    private ContextFactory $contextFactory;

    public function __construct(EntityRepositoryInterface $mappingRepository, ContextFactory $contextFactory)
    {
        $this->mappingRepository = $mappingRepository;
        $this->contextFactory = $contextFactory;
    }

    public function reflectEntities(
        MappedDatasetEntityCollection $mappedEntities,
        PortalNodeKeyInterface $targetPortalNodeKey
    ): void {
        if (!$targetPortalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetPortalNodeKey));
        }

        $targetPortalNodeId = $targetPortalNodeKey->getUuid();
        $index = [];
        $filters = [];
        $createMappings = [];
        $reflectedFilters = [];

        foreach ($mappedEntities->groupByPortalNode() as $mappedEntityGroup) {
            $firstMappedEntity = $mappedEntityGroup->first();

            if (!$firstMappedEntity instanceof MappedDatasetEntityStruct) {
                continue;
            }

            $sourcePortalNodeKey = $firstMappedEntity->getMapping()->getPortalNodeKey();

            if (!$sourcePortalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($sourcePortalNodeKey));
            }

            $sourcePortalNodeId = $sourcePortalNodeKey->getUuid();

            $filtersPerPortalNode = [];

            /** @var MappedDatasetEntityStruct $mappedEntity */
            foreach ($mappedEntityGroup as $key => $mappedEntity) {
                $mappedEntity->getDatasetEntity()->unattach(PrimaryKeySharingMappingStruct::class);

                $primaryKey = $mappedEntity->getMapping()->getExternalId();

                $mappingNodeKey = $mappedEntity->getMapping()->getMappingNodeKey();

                if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
                }

                $mappingNodeId = $mappingNodeKey->getUuid();

                $index[$mappingNodeId][] = $key;

                if ($primaryKey === null) {
                    continue;
                }

                $filtersPerPortalNode[] = new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('externalId', $primaryKey),
                    new EqualsFilter('mappingNodeId', $mappingNodeId),
                ]);

                $reflectedFilters[] = $mappingNodeId;

                $mappingId = Uuid::uuid4()->getHex();

                $createMappings[$sourcePortalNodeId.$mappingNodeId.$primaryKey] = [
                    'id' => $mappingId,
                    'externalId' => $primaryKey,
                    'mappingNodeId' => $mappingNodeId,
                    'portalNodeId' => $sourcePortalNodeId,
                ];
            }

            if ($filtersPerPortalNode) {
                $filters[] = new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('portalNodeId', $sourcePortalNodeId),
                    new MultiFilter(MultiFilter::CONNECTION_OR, $filtersPerPortalNode),
                ]);
            }
        }

        if ($filters === []) {
            return;
        }

        $criteria = (new Criteria())->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, $filters)
        );

        $context = $this->contextFactory->create();

        /** @var MappingEntity $mapping */
        foreach ($this->mappingRepository->search($criteria, $context)->getIterator() as $mapping) {
            $key = $mapping->getPortalNodeId().$mapping->getMappingNodeId().$mapping->getExternalId();
            unset($createMappings[$key]);
        }

        if ($createMappings) {
            $this->mappingRepository->create(\array_values($createMappings), $context);
        }

        $criteria = (new Criteria())->addFilter(
            new EqualsFilter('portalNodeId', $targetPortalNodeId),
            new EqualsAnyFilter('mappingNodeId', $reflectedFilters),
            new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsFilter('externalId', null),
            ]),
            new EqualsFilter('deletedAt', null),
        );

        /** @var MappingEntity $mapping */
        foreach ($this->mappingRepository->search($criteria, $context)->getIterator() as $mapping) {
            $reflectionMapping = null;

            foreach ($index[$mapping->getMappingNodeId()] ?? [] as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                if (!$reflectionMapping instanceof PrimaryKeySharingMappingStruct) {
                    $reflectionMapping = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getDatasetEntityClassName(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey(),
                        $mappedEntity->getMapping()->getMappingNodeKey()
                    );

                    $reflectionMapping->setForeignKey($mapping->getExternalId());
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey($mapping->getExternalId());
                $reflectionMapping->addOwner($mappedEntity->getDatasetEntity());
            }

            unset($index[$mapping->getMappingNodeId()]);
        }

        foreach ($index as $keys) {
            /** @var PrimaryKeySharingMappingStruct[] $reflectionMappingCache */
            $reflectionMappingCache = [];

            foreach ($keys as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey();

                if (!$sourcePortalNodeKey instanceof PortalNodeStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($sourcePortalNodeKey));
                }

                $cacheKey = \sprintf(
                    '%s;%s',
                    $sourcePortalNodeKey->getUuid(),
                    $mappedEntity->getMapping()->getExternalId()
                );

                if (!(($reflectionMappingCache[$cacheKey] ?? null) instanceof PrimaryKeySharingMappingStruct)) {
                    $reflectionMappingCache[$cacheKey] = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getDatasetEntityClassName(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey(),
                        $mappedEntity->getMapping()->getMappingNodeKey(),
                    );
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey(null);
                $reflectionMappingCache[$cacheKey]->addOwner($mappedEntity->getDatasetEntity());
            }
        }
    }
}
