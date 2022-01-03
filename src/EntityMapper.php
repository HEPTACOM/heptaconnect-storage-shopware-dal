<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityMapperContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class EntityMapper extends EntityMapperContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappingNodes;

    private EntityRepositoryInterface $mappings;

    private EntityTypeAccessor $entityTypeAccessor;

    private ContextFactory $contextFactory;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappingNodes,
        EntityRepositoryInterface $mappings,
        EntityTypeAccessor $entityTypeAccessor,
        ContextFactory $contextFactory
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodes = $mappingNodes;
        $this->mappings = $mappings;
        $this->entityTypeAccessor = $entityTypeAccessor;
        $this->contextFactory = $contextFactory;
    }

    public function mapEntities(
        DatasetEntityCollection $entityCollection,
        PortalNodeKeyInterface $portalNodeKey
    ): MappedDatasetEntityCollection {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $context = $this->contextFactory->create();
        $datasetEntities = \iterable_to_array($entityCollection);
        $neededTypes = \array_map('get_class', $datasetEntities);
        $typeIds = $this->entityTypeAccessor->getIdsForTypes($neededTypes, $context);

        $readMappingNodes = [];
        $readMappingNodesIndex = [];
        $createMappingNodes = [];
        $readMappings = [];
        $resultMappings = [];

        /** @var DatasetEntityContract $entity */
        foreach ($datasetEntities as $key => $entity) {
            $primaryKey = $entity->getPrimaryKey();
            $type = \get_class($entity);

            if ($primaryKey === null) {
                continue;
            }

            $typeId = $typeIds[$type];
            $readMappingNodes[$key] = [
                'externalId' => $primaryKey,
                'type' => $type,
            ];

            $readMappingNodesIndex[$type][$primaryKey][] = $key;

            $createMappingNodes[$key] = [
                'typeId' => $typeId,
                'originPortalNodeId' => $portalNodeId,
                'mappings' => [
                    [
                        'externalId' => $primaryKey,
                        'portalNodeId' => $portalNodeId,
                    ],
                ],
            ];
        }

        $createMappingNodes = \array_unique($createMappingNodes, \SORT_REGULAR);

        if ($readMappingNodes !== []) {
            /** @var MappingNodeEntity $mappingNode */
            foreach ($this->getMappingNodes($readMappingNodes, $typeIds, $portalNodeId, $context) as $mappingNode) {
                $mappings = $mappingNode->getMappings();

                if (!$mappings instanceof MappingCollection) {
                    continue;
                }

                $mapping = $mappings->first();

                if (!$mapping instanceof MappingEntity) {
                    continue;
                }

                $type = $mappingNode->getType()->getType();

                foreach ($readMappingNodesIndex[$type][$mapping->getExternalId()] ?? [] as $key) {
                    unset($createMappingNodes[$key]);
                    $resultMappings[$key] = $mapping;
                }
            }
        }

        if ($createMappingNodes !== []) {
            /** @var MappingNodeKeyInterface[] $mappingNodeKeys */
            $mappingNodeKeys = \iterable_to_array($this->storageKeyGenerator->generateKeys(
                MappingNodeKeyInterface::class,
                \count($createMappingNodes)
            ));

            foreach (\array_keys($createMappingNodes) as $key) {
                $mappingNodeKey = \array_shift($mappingNodeKeys);

                if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
                }

                $mappingNodeId = $mappingNodeKey->getUuid();

                $createMappingNodes[$key]['id'] = $mappingNodeId;
                $readMappings[$key] = $mappingNodeId;
            }

            $this->mappingNodes->create(\array_values($createMappingNodes), $context);
        }

        if ($readMappings !== []) {
            $criteria = (new Criteria())->addFilter(
                new EqualsFilter('portalNodeId', $portalNodeId),
                new EqualsAnyFilter('mappingNodeId', $readMappings),
                new EqualsFilter('deletedAt', null)
            );

            $criteria->addAssociation('mappingNode.type');

            /** @var MappingEntity $mapping */
            foreach ($this->mappings->search($criteria, $context)->getIterator() as $mapping) {
                $type = $mapping->getMappingNode()->getType()->getType();

                foreach ($readMappingNodesIndex[$type][$mapping->getExternalId()] ?? [] as $key) {
                    $resultMappings[$key] = $mapping;
                }
            }
        }

        $mappedDatasetEntityCollection = new MappedDatasetEntityCollection();

        foreach ($datasetEntities as $key => $entity) {
            if (isset($resultMappings[$key])) {
                $mappedDatasetEntityCollection->push([
                    new MappedDatasetEntityStruct($resultMappings[$key], $entity),
                ]);
            }
        }

        return $mappedDatasetEntityCollection;
    }

    private function getMappingNodes(array $readMappingNodes, array $typeIds, string $portalNodeId, Context $context): iterable
    {
        $filtersByType = [];
        foreach ($readMappingNodes as $entity) {
            $filtersByType[$typeIds[$entity['type']]][$entity['externalId']] = true;
        }

        foreach ($filtersByType as $typeId => $externalIds) {
            $criteria = (new Criteria())->addFilter(
                new EqualsFilter('typeId', $typeId),
                new EqualsFilter('deletedAt', null),
                new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('mappings.portalNodeId', $portalNodeId),
                    new EqualsAnyFilter('mappings.externalId', \array_keys($externalIds)),
                ])
            );

            $criteria->addAssociation('type');

            $criteria->getAssociation('mappings')->addFilter(
                new EqualsFilter('deletedAt', null),
                new EqualsFilter('portalNodeId', $portalNodeId),
                new EqualsAnyFilter('externalId', \array_keys($externalIds))
            );

            yield from $this->mappingNodes->search($criteria, $context)->getIterator();
        }
    }
}
