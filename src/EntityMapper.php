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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class EntityMapper extends EntityMapperContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappingNodes;

    private EntityRepositoryInterface $datasetEntityTypes;

    private EntityRepositoryInterface $mappings;

    private ContextFactory $contextFactory;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappingNodes,
        EntityRepositoryInterface $datasetEntityTypes,
        EntityRepositoryInterface $mappings,
        ContextFactory $contextFactory
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodes = $mappingNodes;
        $this->datasetEntityTypes = $datasetEntityTypes;
        $this->mappings = $mappings;
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
        $datasetEntities = iterable_to_array($entityCollection);

        /** @var DatasetEntityTypeCollection $datasetTypeEntities */
        $datasetTypeEntities = $this->datasetEntityTypes->search(new Criteria(), $context)->getEntities();
        $typeIds = $datasetTypeEntities->groupByType();

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

            if (\is_null($typeId)) {
                // todo create type
                continue;
            }

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

        if ($readMappingNodes) {
            $filters = [];

            foreach ($readMappingNodes as $key => $entity) {
                $filters[] = new MultiFilter(MultiFilter::CONNECTION_AND, [
                    new EqualsFilter('mappings.externalId', $entity['externalId']),
                    new EqualsFilter('typeId', $typeIds[$entity['type']]),
                ]);
            }

            $criteria = (new Criteria())->addFilter(
                new EqualsFilter('mappings.portalNodeId', $portalNodeId),
                new MultiFilter(MultiFilter::CONNECTION_OR, $filters)
            );

            $criteria->addAssociation('type');

            $criteria->getAssociation('mappings')->addFilter(
                new EqualsFilter('portalNodeId', $portalNodeId),
                new NotFilter(MultiFilter::CONNECTION_OR, [
                    new EqualsFilter('externalId', null),
                ])
            );

            /** @var MappingNodeEntity $mappingNode */
            foreach ($this->mappingNodes->search($criteria, $context)->getIterator() as $mappingNode) {
                $mapping = $mappingNode->getMappings()->first();

                if (!$mapping instanceof MappingEntity) {
                    continue;
                }

                $type = $mappingNode->getType()->getType();

                foreach ($readMappingNodesIndex[$type][$mapping->getExternalId()] ?? [] as $key) {
                    unset($createMappingNodes[$key], $readMappings[$key]);
                    $resultMappings[$key] = $mapping;
                }
            }
        }

        if ($createMappingNodes) {
            foreach (\array_keys($createMappingNodes) as $key) {
                $mappingNodeKey = $this->storageKeyGenerator->generateKey(MappingNodeKeyInterface::class);

                if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
                }

                $mappingNodeId = $mappingNodeKey->getUuid();

                $createMappingNodes[$key]['id'] = $mappingNodeId;
                $readMappings[$key] = $mappingNodeId;
            }

            $this->mappingNodes->create(\array_values($createMappingNodes), $context);
        }

        if ($readMappings) {
            $criteria = (new Criteria())->addFilter(
                new EqualsFilter('portalNodeId', $portalNodeId),
                new EqualsAnyFilter('mappingNodeId', $readMappings)
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
}
