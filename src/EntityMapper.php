<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Dataset\Base\DatasetEntity;
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

    private EntityRepositoryInterface $datasetEntityTypes;

    private EntityRepositoryInterface $mappings;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappingNodes,
        EntityRepositoryInterface $datasetEntityTypes,
        EntityRepositoryInterface $mappings
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodes = $mappingNodes;
        $this->datasetEntityTypes = $datasetEntityTypes;
        $this->mappings = $mappings;
    }

    public function mapEntities(
        DatasetEntityCollection $entityCollection,
        PortalNodeKeyInterface $portalNodeKey
    ): MappedDatasetEntityCollection {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $context = Context::createDefaultContext();
        $datasetEntities = iterable_to_array($entityCollection);

        /** @var DatasetEntityTypeCollection $datasetTypeEntities */
        $datasetTypeEntities = $this->datasetEntityTypes->search(new Criteria(), $context)->getEntities();
        $typeIds = $datasetTypeEntities->groupByType();

        $readMappingNodes = [];
        $readMappingNodesIndex = [];
        $createMappingNodes = [];
        $readMappings = [];
        $resultMappings = [];

        /** @var DatasetEntity $entity */
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

            $readMappingNodesIndex[$type][$primaryKey] = $key;

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
                new EqualsFilter('portalNodeId', $portalNodeId)
            );

            /** @var MappingNodeEntity $mappingNode */
            foreach ($this->mappingNodes->search($criteria, $context)->getIterator() as $mappingNode) {
                /** @var MappingEntity $mapping */
                $mapping = $mappingNode->getMappings()->first();
                $type = $mappingNode->getType()->getType();

                $key = $readMappingNodesIndex[$type][$mapping->getExternalId()];
                unset($createMappingNodes[$key], $readMappings[$key]);
                $resultMappings[$type][$key] = $mapping;
            }
        }

        if ($createMappingNodes) {
            foreach (array_keys($createMappingNodes) as $key) {
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
                $key = $readMappingNodesIndex[$type][$mapping->getExternalId()];
                $resultMappings[$type][$key] = $mapping;
            }
        }

        $mappedDatasetEntityCollection = new MappedDatasetEntityCollection();

        foreach ($datasetEntities as $key => $entity) {
            $type = \get_class($entity);

            if (isset($resultMappings[$type][$key])) {
                $mappedDatasetEntityCollection->push([
                    new MappedDatasetEntityStruct($resultMappings[$type][$key], $entity),
                ]);
            }
        }

        return $mappedDatasetEntityCollection;
    }
}
