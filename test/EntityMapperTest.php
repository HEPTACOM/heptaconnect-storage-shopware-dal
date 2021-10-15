<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 */
class EntityMapperTest extends TestCase
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
    public function testMap(DatasetEntityContract $entity): void
    {
        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        /** @var ContextFactory $contextFactory */
        $contextFactory = $this->kernel->getContainer()->get(ContextFactory::class);
        $entityTypeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_entity_type');
        $mappingNodeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping_node');
        $mappingRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping');
        $portalNodeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_portal_node');
        $entityTypeAccessor = new EntityTypeAccessor($entityTypeRepository);
        $mapper = new EntityMapper(new StorageKeyGenerator(), $mappingNodeRepository, $mappingRepository, $entityTypeAccessor, $contextFactory);
        $portalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());

        $entity->setPrimaryKey($entity->getPrimaryKey() ?? Uuid::randomHex());
        $context = Context::createDefaultContext();
        $getClass = \get_class($entity);
        $typeId = Uuid::randomHex();
        $portalNodeRepository->create([[
            'id' => $portalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ]], $context);
        $entityTypeRepository->create([[
            'id' => $typeId,
            'type' => $getClass,
        ]], $context);
        $nodeId = Uuid::randomHex();
        $mappingNodeRepository->create([[
            'id' => $nodeId,
            'typeId' => $typeId,
            'originPortalNodeId' => $portalNodeKey->getUuid(),
        ]], $context);
        $mappingRepository->create([[
            'id' => Uuid::randomHex(),
            'mappingNodeId' => $nodeId,
            'portalNodeId' => $portalNodeKey->getUuid(),
            'externalId' => $entity->getPrimaryKey(),
        ]], $context);

        $mappedEntities = $mapper->mapEntities(new DatasetEntityCollection([$entity, (new DeepCloneContract())->deepClone($entity)]), $portalNodeKey);
        /** @var MappedDatasetEntityStruct|null $firstEntity */
        $firstEntity = $mappedEntities->first();
        /** @var MappedDatasetEntityStruct|null $secondEntity */
        $secondEntity = $mappedEntities->last();

        static::assertNotNull($firstEntity);
        static::assertInstanceOf(MappedDatasetEntityStruct::class, $firstEntity);
        static::assertEquals($entity->getPrimaryKey(), $firstEntity->getMapping()->getExternalId());

        static::assertNotNull($secondEntity);
        static::assertInstanceOf(MappedDatasetEntityStruct::class, $secondEntity);
        static::assertEquals($entity->getPrimaryKey(), $secondEntity->getMapping()->getExternalId());

        static::assertNotSame($firstEntity, $secondEntity);
        static::assertCount(2, $mappedEntities);
    }
}
