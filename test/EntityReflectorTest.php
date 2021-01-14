<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Core\Mapping\Support\ReflectionMapping;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\Support\DatasetEntityTracker;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use function DeepCopy\deep_copy;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector
 */
class EntityReflectorTest extends TestCase
{
    use ProvideEntitiesTrait;

    protected ShopwareKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new ShopwareKernel();
        $this->kernel->boot();

        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->rollBack();
        $this->kernel->shutdown();
    }

    /**
     * @dataProvider provideEntities
     */
    public function testReflectToDifferentPortalNode($datasetEntity): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        DatasetEntityTracker::instance()->listen();
        deep_copy($datasetEntity);
        $tracked = DatasetEntityTracker::instance()->retrieve();

        $types = [];
        $nodes = [];
        $mappings = [];
        $mappingPairs = [];

        /** @var DatasetEntityInterface $entity */
        foreach ($tracked as $entity) {
            $entityClass = \get_class($entity);
            $typeId = ($types[$entityClass] ??= [
                'id' => Uuid::randomHex(),
                'type' => $entityClass,
            ])['id'];
            $nodeId = Uuid::randomHex();
            $nodes[] = [
                'id' => $nodeId,
                'typeId' => $typeId,
                'originPortalNodeId' => $sourcePortalNodeKey->getUuid(),
            ];
            $sourceId = Uuid::randomHex();
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $sourcePortalNodeKey->getUuid(),
                'externalId' => $sourceId,
            ];
            $targetId = Uuid::randomHex();
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $targetPortalNodeKey->getUuid(),
                'externalId' => $targetId,
            ];

            $mappingPairs['object_hash'][spl_object_hash($entity)] = $targetId;
            $mappingPairs['reflection_mapping'][$sourceId] = $targetId;

            $entity->setPrimaryKey($sourceId);
            $mappedEntities->push([new MappedDatasetEntityStruct($this->getMapping($entityClass, $sourceId, $sourcePortalNodeKey, $nodeId), $entity)]);
        }

        $portalNodeRepository->create([[
            'id' => $sourcePortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ], [
            'id' => $targetPortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ]], $context);
        $datasetEntityTypeRepository->create(\array_values($types), $context);
        $mappingNodeRepository->create(\array_values($nodes), $context);
        $mappingRepository->create(\array_values($mappings), $context);

        $reflector->reflectEntities($mappedEntities, $targetPortalNodeKey);

        /** @var MappedDatasetEntityStruct $mappedEntity */
        foreach ($mappedEntities as $mappedEntity) {
            $entity = $mappedEntity->getDatasetEntity();
            /** @var ReflectionMapping $reflectionMapping */
            $reflectionMapping = $entity->getAttachment(ReflectionMapping::class);

            static::assertSame($mappingPairs['object_hash'][spl_object_hash($entity)], $entity->getPrimaryKey());
            static::assertSame($mappingPairs['reflection_mapping'][$reflectionMapping->getExternalId()], $entity->getPrimaryKey());
        }
    }

    /**
     * @dataProvider provideEntities
     */
    public function testReflectToDifferentPortalNodeWithMoreThanTwoMappings($datasetEntity): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $unrelatedPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        DatasetEntityTracker::instance()->listen();
        deep_copy($datasetEntity);
        $tracked = DatasetEntityTracker::instance()->retrieve();

        $types = [];
        $nodes = [];
        $mappings = [];
        $mappingPairs = [];

        /** @var DatasetEntityInterface $entity */
        foreach ($tracked as $entity) {
            $entityClass = \get_class($entity);
            $typeId = ($types[$entityClass] ??= [
                'id' => Uuid::randomHex(),
                'type' => $entityClass,
            ])['id'];
            $nodeId = Uuid::randomHex();
            $nodes[] = [
                'id' => $nodeId,
                'typeId' => $typeId,
                'originPortalNodeId' => $sourcePortalNodeKey->getUuid(),
            ];
            $sourceId = Uuid::randomHex();
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $sourcePortalNodeKey->getUuid(),
                'externalId' => $sourceId,
            ];
            $targetId = Uuid::randomHex();
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $targetPortalNodeKey->getUuid(),
                'externalId' => $targetId,
            ];
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $unrelatedPortalNodeKey->getUuid(),
                'externalId' => Uuid::randomHex(),
            ];

            $mappingPairs['object_hash'][spl_object_hash($entity)] = $targetId;
            $mappingPairs['reflection_mapping'][$sourceId] = $targetId;

            $entity->setPrimaryKey($sourceId);
            $mappedEntities->push([new MappedDatasetEntityStruct($this->getMapping($entityClass, $sourceId, $sourcePortalNodeKey, $nodeId), $entity)]);
        }

        $portalNodeRepository->create([[
            'id' => $sourcePortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ], [
            'id' => $targetPortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ], [
            'id' => $unrelatedPortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ]], $context);
        $datasetEntityTypeRepository->create(\array_values($types), $context);
        $mappingNodeRepository->create(\array_values($nodes), $context);
        $mappingRepository->create(\array_values($mappings), $context);

        $reflector->reflectEntities($mappedEntities, $targetPortalNodeKey);

        /** @var MappedDatasetEntityStruct $mappedEntity */
        foreach ($mappedEntities as $mappedEntity) {
            $entity = $mappedEntity->getDatasetEntity();
            /** @var ReflectionMapping $reflectionMapping */
            $reflectionMapping = $entity->getAttachment(ReflectionMapping::class);

            static::assertSame($mappingPairs['object_hash'][spl_object_hash($entity)], $entity->getPrimaryKey());
            static::assertSame($mappingPairs['reflection_mapping'][$reflectionMapping->getExternalId()], $entity->getPrimaryKey());
        }
    }

    /**
     * @dataProvider provideEntities
     */
    public function testReflectToDifferentPortalNodeWithNoMappingsYet($datasetEntity): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        DatasetEntityTracker::instance()->listen();
        deep_copy($datasetEntity);
        $tracked = DatasetEntityTracker::instance()->retrieve();

        $types = [];
        $nodes = [];
        $mappings = [];
        $mappingPairs = [];

        /** @var DatasetEntityInterface $entity */
        foreach ($tracked as $entity) {
            $entityClass = \get_class($entity);
            $typeId = ($types[$entityClass] ??= [
                'id' => Uuid::randomHex(),
                'type' => $entityClass,
            ])['id'];
            $nodeId = Uuid::randomHex();
            $nodes[] = [
                'id' => $nodeId,
                'typeId' => $typeId,
                'originPortalNodeId' => $sourcePortalNodeKey->getUuid(),
            ];
            $sourceId = Uuid::randomHex();
            $mappings[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $sourcePortalNodeKey->getUuid(),
                'externalId' => $sourceId,
            ];

            $entity->setPrimaryKey($sourceId);
            $mappedEntities->push([new MappedDatasetEntityStruct($this->getMapping($entityClass, $sourceId, $sourcePortalNodeKey, $nodeId), $entity)]);
        }

        $portalNodeRepository->create([[
            'id' => $sourcePortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ], [
            'id' => $targetPortalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ]], $context);
        $datasetEntityTypeRepository->create(\array_values($types), $context);
        $mappingNodeRepository->create(\array_values($nodes), $context);
        $mappingRepository->create(\array_values($mappings), $context);

        $reflector->reflectEntities($mappedEntities, $targetPortalNodeKey);

        /** @var MappedDatasetEntityStruct $mappedEntity */
        foreach ($mappedEntities as $mappedEntity) {
            static::assertNull($mappedEntity->getDatasetEntity()->getPrimaryKey());
        }
    }

    private function getMapping(
        string $entityClass,
        string $sourceId,
        PortalNodeStorageKey $sourcePortalNodeKey,
        string $nodeId
    ): MappingInterface {
        return new class (
            $entityClass,
            $sourceId,
            $sourcePortalNodeKey,
            new MappingNodeStorageKey($nodeId)
        ) implements MappingInterface {
            private ?string $externalId;

            private PortalNodeKeyInterface $portalNodeKey;

            private MappingNodeKeyInterface $mappingNodeKey;

            private string $type;

            public function __construct(
                string $type,
                ?string $externalId,
                PortalNodeKeyInterface $portalNodeKey,
                MappingNodeKeyInterface $mappingNodeKey
            )
            {
                $this->type = $type;
                $this->externalId = $externalId;
                $this->portalNodeKey = $portalNodeKey;
                $this->mappingNodeKey = $mappingNodeKey;
            }

            public function getExternalId(): ?string
            {
                return $this->externalId;
            }

            public function setExternalId(?string $externalId): MappingInterface
            {
                $this->externalId = $externalId;

                return $this;
            }

            public function getPortalNodeKey(): PortalNodeKeyInterface
            {
                return $this->portalNodeKey;
            }

            public function getMappingNodeKey(): MappingNodeKeyInterface
            {
                return $this->mappingNodeKey;
            }

            public function getDatasetEntityClassName(): string
            {
                return $this->type;
            }
        };
    }
}
