<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Core\Mapping\Contract\MappingServiceInterface;
use Heptacom\HeptaConnect\Core\Mapping\Support\ReflectionMapping;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\EntityReflectorContract;

class EntityReflector extends EntityReflectorContract
{
    private MappingServiceInterface $mappingService;

    public function __construct(MappingServiceInterface $mappingService)
    {
        $this->mappingService = $mappingService;
    }

    public function reflectEntities(
        MappedDatasetEntityCollection $entities,
        PortalNodeKeyInterface $portalNodeKey
    ): void {
        /** @var MappedDatasetEntityStruct $entity */
        foreach ($entities as $entity) {
            $sourceMapping = $entity->getMapping();
            $targetMapping = $this->mappingService->reflect($sourceMapping, $portalNodeKey);

            $reflectionMapping = (new ReflectionMapping())
                ->setPortalNodeKey($sourceMapping->getPortalNodeKey())
                ->setMappingNodeKey($sourceMapping->getMappingNodeKey())
                ->setDatasetEntityClassName($sourceMapping->getDatasetEntityClassName())
                ->setExternalId($sourceMapping->getExternalId())
            ;

            $entity->getDatasetEntity()->attach($reflectionMapping);
            $entity->getDatasetEntity()->setPrimaryKey($targetMapping->getExternalId());
        }
    }
}
