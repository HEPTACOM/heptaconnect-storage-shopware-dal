<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Dataset\Base\Support\TrackedEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Uuid\Uuid;
use function DeepCopy\deep_copy;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper
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
    public function testMap(DatasetEntityInterface $entity): void
    {
        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $datasetEntityTypeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_dataset_entity_type');
        $mappingNodeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping_node');
        $mappingRepository = $definitionInstanceRegistry->getRepository('heptaconnect_mapping');
        $portalNodeRepository = $definitionInstanceRegistry->getRepository('heptaconnect_portal_node');
        $mapper = new EntityMapper(new StorageKeyGenerator(), $mappingNodeRepository, $datasetEntityTypeRepository, $mappingRepository);
        $portalNodeKey = new PortalNodeStorageKey(Uuid::randomHex());

        $entity->setPrimaryKey($entity->getPrimaryKey() ?? Uuid::randomHex());
        $context = Context::createDefaultContext();
        $getClass = \get_class($entity);
        $typeId = Uuid::randomHex();
        $portalNodeRepository->create([[
            'id' => $portalNodeKey->getUuid(),
            'className' => PortalContract::class,
        ]], $context);
        $datasetEntityTypeRepository->create([[
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

        $mappedEntities = $mapper->mapEntities(new TrackedEntityCollection([$entity, deep_copy($entity)]), $portalNodeKey);
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
