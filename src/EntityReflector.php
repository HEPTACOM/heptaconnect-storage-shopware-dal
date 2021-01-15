<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Support\ReflectionMapping;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class EntityReflector extends EntityReflectorContract
{
    private EntityRepositoryInterface $mappingRepository;

    public function __construct(EntityRepositoryInterface $mappingRepository)
    {
        $this->mappingRepository = $mappingRepository;
    }

    public function reflectEntities(
        MappedDatasetEntityCollection $mappedEntities,
        PortalNodeKeyInterface $targetPortalNodeKey
    ): void {
        if (!$targetPortalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetPortalNodeKey));
        }

        $targetPortalNodeId = $targetPortalNodeKey->getUuid();

        $context = Context::createDefaultContext();

        $index = [];
        $filters = [];
        $createMappings = [];
        $reflectedFilters = [];

        /** @var MappedDatasetEntityStruct $mappedEntity */
        foreach ($mappedEntities as $key => $mappedEntity) {
            $primaryKey = $mappedEntity->getMapping()->getExternalId();
            $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey();
            $mappingNodeKey = $mappedEntity->getMapping()->getMappingNodeKey();

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
            }

            $mappingNodeId = $mappingNodeKey->getUuid();

            $index[$mappingNodeId][] = $key;

            if ($primaryKey === null) {
                continue;
            }

            if (!$sourcePortalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($sourcePortalNodeKey));
            }

            $sourcePortalNodeId = $sourcePortalNodeKey->getUuid();

            $filters[] = new MultiFilter(MultiFilter::CONNECTION_AND, [
                new EqualsFilter('externalId', $primaryKey),
                new EqualsFilter('mappingNodeId', $mappingNodeId),
                new EqualsFilter('portalNodeId', $sourcePortalNodeId),
            ]);

            $reflectedFilters[] = new EqualsFilter('mappingNodeId', $mappingNodeId);

            $mappingId = Uuid::uuid4()->getHex();

            $createMappings[$sourcePortalNodeId . $mappingNodeId . $primaryKey] = [
                'id' => $mappingId,
                'externalId' => $primaryKey,
                'mappingNodeId' => $mappingNodeId,
                'portalNodeId' => $sourcePortalNodeId,
            ];
        }

        $criteria = (new Criteria())->addFilter(
            new MultiFilter(MultiFilter::CONNECTION_OR, $filters)
        );

        /** @var MappingEntity $mapping */
        foreach ($this->mappingRepository->search($criteria, $context)->getIterator() as $mapping) {
            $key = $mapping->getPortalNodeId() . $mapping->getMappingNodeId() . $mapping->getExternalId();
            unset($createMappings[$key]);
        }

        if ($createMappings) {
            $this->mappingRepository->create(\array_values($createMappings), $context);
        }

        $criteria = (new Criteria())->addFilter(
            new EqualsFilter('portalNodeId', $targetPortalNodeId),
            new MultiFilter(MultiFilter::CONNECTION_OR, $reflectedFilters),
        );

        /** @var MappingEntity $mapping */
        foreach ($this->mappingRepository->search($criteria, $context)->getIterator() as $mapping) {
            foreach ($index[$mapping->getMappingNodeId()] ?? [] as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                $reflectionMapping = new ReflectionMapping();
                $reflectionMapping->setPortalNodeKey($mappedEntity->getMapping()->getPortalNodeKey());
                $reflectionMapping->setMappingNodeKey($mappedEntity->getMapping()->getMappingNodeKey());
                $reflectionMapping->setDatasetEntityClassName($mappedEntity->getMapping()->getDatasetEntityClassName());
                $reflectionMapping->setExternalId($mappedEntity->getMapping()->getExternalId());

                $mappedEntity->getDatasetEntity()->attach($reflectionMapping);
                $mappedEntity->getDatasetEntity()->setPrimaryKey($mapping->getExternalId());
            }

            unset($index[$mapping->getMappingNodeId()]);
        }

        foreach ($index as $keys) {
            foreach ($keys as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                $reflectionMapping = new ReflectionMapping();
                $reflectionMapping->setPortalNodeKey($mappedEntity->getMapping()->getPortalNodeKey());
                $reflectionMapping->setMappingNodeKey($mappedEntity->getMapping()->getMappingNodeKey());
                $reflectionMapping->setDatasetEntityClassName($mappedEntity->getMapping()->getDatasetEntityClassName());
                $reflectionMapping->setExternalId($mappedEntity->getMapping()->getExternalId());

                $mappedEntity->getDatasetEntity()->attach($reflectionMapping);
                $mappedEntity->getDatasetEntity()->setPrimaryKey(null);
            }
        }
    }
}
