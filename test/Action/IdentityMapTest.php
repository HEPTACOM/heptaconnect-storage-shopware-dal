<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Support\Contract\DeepCloneContract;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\ProvideEntitiesTrait;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
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
class IdentityMapTest extends TestCase
{
    use ProvideEntitiesTrait;

    /**
     * @dataProvider provideEntities
     */
    public function testMap(DatasetEntityContract $entity): void
    {
        $facade = $this->createStorageFacade();
        $portalNodeCreate = $facade->getPortalNodeCreateAction();

        /** @var PortalNodeKeyInterface $portalNodeKey */
        $portalNodeKey = $portalNodeCreate->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
        ]))->first()->getPortalNodeKey();

        $connection = $this->kernel->getContainer()->get(Connection::class);
        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $entityTypeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_entity_type');
        $mappingNodeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping_node');
        $mappingRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping');
        $mapper = new IdentityMap(new StorageKeyGenerator(), new EntityTypeAccessor($connection), $connection);

        $entity->setPrimaryKey($entity->getPrimaryKey() ?? Uuid::randomHex());
        $context = Context::createDefaultContext();
        $getClass = \get_class($entity);
        $typeId = Uuid::randomHex();
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

        $mappedEntities = $mapper->map(new IdentityMapPayload($portalNodeKey, new DatasetEntityCollection([$entity, (new DeepCloneContract())->deepClone($entity)])))->getMappedDatasetEntityCollection();

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

    private function createStorageFacade(): StorageFacadeInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);

        return new StorageFacade($connection);
    }
}
