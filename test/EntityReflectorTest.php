<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepObjectIteratorContract;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;

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
        /** @var ContextFactory $contextFactory */
        $contextFactory = $this->kernel->getContainer()->get(ContextFactory::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository, $contextFactory);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        $tracked = new TrackedEntityCollection((new DeepObjectIteratorContract())->iterate($datasetEntity));

        $types = [];
        $nodes = [];
        $mappings = [];
        $mappingPairs = [];

        /** @var DatasetEntityContract $entity */
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

            $mappingPairs['object_hash'][\spl_object_hash($entity)] = $targetId;
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
            /** @var PrimaryKeySharingMappingStruct $reflectionMapping */
            $reflectionMapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

            static::assertSame($mappingPairs['object_hash'][\spl_object_hash($entity)], $entity->getPrimaryKey());
            static::assertSame($mappingPairs['reflection_mapping'][$reflectionMapping->getExternalId()], $entity->getPrimaryKey());
        }
    }

    /**
     * @dataProvider provideEntities
     */
    public function testReflectDatasetEntityTwiceToDifferentPortalNode(DatasetEntityContract $datasetEntity): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        /** @var ContextFactory $contextFactory */
        $contextFactory = $this->kernel->getContainer()->get(ContextFactory::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository, $contextFactory);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        $datasetEntity->setPrimaryKey($datasetEntity->getPrimaryKey() ?? Uuid::randomHex());
        $datasetEntity->attach($datasetEntity);
        $tracked = new TrackedEntityCollection((new DeepObjectIteratorContract())->iterate($datasetEntity));

        $types = [];
        $nodes = [];
        $mappings = [];

        /** @var DatasetEntityContract $entity */
        foreach ($tracked as $entity) {
            $entityClass = \get_class($entity);
            $typeId = ($types[$entityClass] ??= [
                'id' => Uuid::randomHex(),
                'type' => $entityClass,
            ])['id'];
            $sourceId = $entity->getPrimaryKey() ?? Uuid::randomHex();
            $nodeId = ($nodes[$sourceId] ??= [
                'id' => Uuid::randomHex(),
                'typeId' => $typeId,
                'originPortalNodeId' => $sourcePortalNodeKey->getUuid(),
            ])['id'];
            $mappings['s'.$sourceId] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $sourcePortalNodeKey->getUuid(),
                'externalId' => $sourceId,
            ];
            $targetId = Uuid::randomHex();
            $mappings['t'.$sourceId] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $nodeId,
                'portalNodeId' => $targetPortalNodeKey->getUuid(),
                'externalId' => $targetId,
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

        /** @var MappedDatasetEntityStruct|null $first */
        $first = $mappedEntities->first();
        /** @var MappedDatasetEntityStruct|null $last */
        $last = $mappedEntities->last();

        static::assertNotNull($first);
        static::assertInstanceOf(MappedDatasetEntityStruct::class, $first);
        static::assertNotNull($last);
        static::assertInstanceOf(MappedDatasetEntityStruct::class, $last);
        static::assertSame($first->getDatasetEntity()->getPrimaryKey(), $last->getDatasetEntity()->getPrimaryKey());

        $reflectionMappings = [];

        /** @var MappedDatasetEntityStruct $mappedEntity */
        foreach ($mappedEntities as $mappedEntity) {
            $reflectionMapping = $mappedEntity->getDatasetEntity()->getAttachment(PrimaryKeySharingMappingStruct::class);

            if ($reflectionMapping instanceof PrimaryKeySharingMappingStruct) {
                $reflectionMappings[\spl_object_hash($reflectionMapping)] = \count(\iterable_to_array($reflectionMapping->getOwners()));
            }
        }

        static::assertLessThan(\array_sum($reflectionMappings), \count($reflectionMappings));
    }

    /**
     * @dataProvider provideEntities
     */
    public function testReflectToDifferentPortalNodeWithMoreThanTwoMappings($datasetEntity): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        /** @var ContextFactory $contextFactory */
        $contextFactory = $this->kernel->getContainer()->get(ContextFactory::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository, $contextFactory);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $unrelatedPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        $tracked = new TrackedEntityCollection((new DeepObjectIteratorContract())->iterate($datasetEntity));

        $types = [];
        $nodes = [];
        $mappings = [];
        $mappingPairs = [];

        /** @var DatasetEntityContract $entity */
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

            $mappingPairs['object_hash'][\spl_object_hash($entity)] = $targetId;
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
            /** @var PrimaryKeySharingMappingStruct $reflectionMapping */
            $reflectionMapping = $entity->getAttachment(PrimaryKeySharingMappingStruct::class);

            static::assertSame($mappingPairs['object_hash'][\spl_object_hash($entity)], $entity->getPrimaryKey());
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
        /** @var ContextFactory $contextFactory */
        $contextFactory = $this->kernel->getContainer()->get(ContextFactory::class);
        $portalNodeRepository = $definitionRegistration->getRepository('heptaconnect_portal_node');
        $mappingRepository = $definitionRegistration->getRepository('heptaconnect_mapping');
        $mappingNodeRepository = $definitionRegistration->getRepository('heptaconnect_mapping_node');
        $datasetEntityTypeRepository = $definitionRegistration->getRepository('heptaconnect_dataset_entity_type');
        $context = Context::createDefaultContext();
        $reflector = new EntityReflector($mappingRepository, $contextFactory);
        $sourcePortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $targetPortalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());
        $mappedEntities = new MappedDatasetEntityCollection();
        $tracked = new TrackedEntityCollection((new DeepObjectIteratorContract())->iterate($datasetEntity));

        $types = [];
        $nodes = [];
        $mappings = [];

        /** @var DatasetEntityContract $entity */
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
        return new class($entityClass, $sourceId, $sourcePortalNodeKey, new MappingNodeStorageKey($nodeId)) implements MappingInterface {
            private ?string $externalId;

            private PortalNodeKeyInterface $portalNodeKey;

            private MappingNodeKeyInterface $mappingNodeKey;

            private string $type;

            public function __construct(
                string $type,
                ?string $externalId,
                PortalNodeKeyInterface $portalNodeKey,
                MappingNodeKeyInterface $mappingNodeKey
            ) {
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
