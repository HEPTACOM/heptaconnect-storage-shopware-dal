<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Create\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister\MappingPersister;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister\MappingPersister
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 */
class MappingPersisterTest extends TestCase
{
    private MappingPersister $mappingPersister;

    private MappingNodeRepository $mappingNodeRepository;

    private MappingRepository $mappingRepository;

    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    protected function setUp(): void
    {
        parent::setup();

        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->beginTransaction();

        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $shopwareMappingNodeRepository = $definitionInstanceRegistry
            ->getRepository('heptaconnect_mapping_node');
        $shopwareMappingRepository = $definitionInstanceRegistry
            ->getRepository('heptaconnect_mapping');
        $shopwarePortalNodeRepository = $definitionInstanceRegistry
            ->getRepository('heptaconnect_portal_node');
        $shopwareDatasetEntityTypeRepository = $definitionInstanceRegistry
            ->getRepository('heptaconnect_entity_type');

        $this->mappingPersister = new MappingPersister($shopwareMappingRepository, $connection);
        $storageKeyGenerator = new StorageKeyGenerator();
        $contextFactory = new ContextFactory();
        $datasetEntityTypeAccessor = new EntityTypeAccessor($shopwareDatasetEntityTypeRepository);
        $this->mappingNodeRepository = new MappingNodeRepository(
            $storageKeyGenerator,
            $shopwareMappingNodeRepository,
            $shopwareMappingRepository,
            $contextFactory,
            $datasetEntityTypeAccessor
        );
        $this->mappingRepository = new MappingRepository(
            $storageKeyGenerator,
            $shopwareMappingRepository,
            $contextFactory
        );
        $this->portalNodeCreateAction = new PortalNodeCreate($connection, $storageKeyGenerator);
    }

    public function testPersistingSingleEntityMapping(): void
    {
        $externalIdSource = (string) Uuid::uuid4()->getHex();
        $externalIdTarget = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->create($mappingNodeKey, $externalIdTarget);
        $this->mappingPersister->persist($payload);

        $targetMappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));

        static::assertCount(1, $targetMappings);

        /** @var MappingKeyInterface $targetMappingKey */
        $targetMappingKey = \array_shift($targetMappings);
        $targetMapping = $this->mappingRepository->read($targetMappingKey);

        static::assertTrue($targetMapping->getMappingNodeKey()->equals($mappingNodeKey));
        static::assertTrue($targetMapping->getPortalNodeKey()->equals($portalNodeKeyTarget));
        static::assertSame($externalIdTarget, $targetMapping->getExternalId());
        static::assertSame(Simple::class, $targetMapping->getEntityType());
    }

    public function testPersistingSameExternalIdToTwoDifferentMappingNodes(): void
    {
        $externalId1Source = (string) Uuid::uuid4()->getHex();
        $externalId2Source = (string) Uuid::uuid4()->getHex();
        $externalIdTarget = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey1 = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $mappingNodeKey2 = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey1, $externalId1Source);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey2, $externalId2Source);

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->create($mappingNodeKey1, $externalIdTarget);
        $payload->create($mappingNodeKey2, $externalIdTarget);

        $failed = false;

        try {
            $this->mappingPersister->persist($payload);
        } catch (\Throwable $t) {
            $failed = true;
        }

        if (!$failed) {
            static::fail('mappingPersister->persist should have failed');
        }

        $target1Mappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey1),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        static::assertCount(0, $target1Mappings);
        $target2Mappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey2),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        static::assertCount(0, $target2Mappings);
    }

    public function testCreatingDifferentExternalIdToTwoSameMappingNodes(): void
    {
        $externalIdSource = (string) Uuid::uuid4()->getHex();
        $externalId1Target = (string) Uuid::uuid4()->getHex();
        $externalId2Target = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->create($mappingNodeKey, $externalId1Target);
        $payload->create($mappingNodeKey, $externalId2Target);

        $failed = false;

        try {
            $this->mappingPersister->persist($payload);
        } catch (\Throwable $t) {
            $failed = true;
        }

        if (!$failed) {
            static::fail('mappingPersister->persist should have failed');
        }

        $targetMappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        static::assertCount(0, $targetMappings);
    }

    public function testCreatingAndUpdatingDifferentExternalIdToTwoSameMappingNodes(): void
    {
        $externalIdSource = (string) Uuid::uuid4()->getHex();
        $externalId1Target = (string) Uuid::uuid4()->getHex();
        $externalId2Target = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->create($mappingNodeKey, $externalId1Target);
        $payload->update($mappingNodeKey, $externalId2Target);

        $failed = false;

        try {
            $this->mappingPersister->persist($payload);
        } catch (\Throwable $t) {
            $failed = true;
        }

        if (!$failed) {
            static::fail('mappingPersister->persist should have failed');
        }

        $targetMappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        static::assertCount(0, $targetMappings);
    }

    public function testSwappingExternalIdsOfTwoMappings(): void
    {
        $externalIdSourceA = (string) Uuid::uuid4()->getHex();
        $externalIdTargetA = (string) Uuid::uuid4()->getHex();

        $externalIdSourceB = (string) Uuid::uuid4()->getHex();
        $externalIdTargetB = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKeyA = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $mappingNodeKeyB = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKeyA, $externalIdSourceA);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKeyB, $externalIdSourceB);
        $this->mappingRepository->create($portalNodeKeyTarget, $mappingNodeKeyA, $externalIdTargetA);
        $this->mappingRepository->create($portalNodeKeyTarget, $mappingNodeKeyB, $externalIdTargetB);

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->update($mappingNodeKeyA, $externalIdTargetB);
        $payload->update($mappingNodeKeyB, $externalIdTargetA);

        $this->mappingPersister->persist($payload);

        $targetMappingsA = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKeyA),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));

        /** @var MappingKeyInterface $targetMappingKeyA */
        $targetMappingKeyA = \array_shift($targetMappingsA);
        $targetMappingA = $this->mappingRepository->read($targetMappingKeyA);

        $targetMappingsB = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKeyB),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));

        /** @var MappingKeyInterface $targetMappingKeyB */
        $targetMappingKeyB = \array_shift($targetMappingsB);
        $targetMappingB = $this->mappingRepository->read($targetMappingKeyB);

        static::assertTrue($targetMappingA->getMappingNodeKey()->equals($mappingNodeKeyA));
        static::assertTrue($targetMappingA->getPortalNodeKey()->equals($portalNodeKeyTarget));
        static::assertSame($externalIdTargetB, $targetMappingA->getExternalId());
        static::assertSame(Simple::class, $targetMappingA->getEntityType());

        static::assertTrue($targetMappingB->getMappingNodeKey()->equals($mappingNodeKeyB));
        static::assertTrue($targetMappingB->getPortalNodeKey()->equals($portalNodeKeyTarget));
        static::assertSame($externalIdTargetA, $targetMappingB->getExternalId());
        static::assertSame(Simple::class, $targetMappingB->getEntityType());
    }

    public function testDeletingMappingNode(): void
    {
        $externalIdSource = (string) Uuid::uuid4()->getHex();
        $externalIdTarget = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey, $externalIdSource);
        $this->mappingRepository->create($portalNodeKeyTarget, $mappingNodeKey, $externalIdTarget);

        static::assertCount(1, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));
        static::assertCount(1, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        )));

        $payload = new MappingPersistPayload($portalNodeKeyTarget);
        $payload->delete($mappingNodeKey);

        $this->mappingPersister->persist($payload);

        static::assertCount(1, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));
        static::assertCount(0, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        )));

        $payload = new MappingPersistPayload($portalNodeKeySource);
        $payload->delete($mappingNodeKey);

        $this->mappingPersister->persist($payload);

        static::assertCount(0, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));
        static::assertCount(0, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        )));
    }

    public function testDeletingMappingNodesTwice(): void
    {
        $externalIdSource = (string) Uuid::uuid4()->getHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();

        $mappingNodeKey = $this->mappingNodeRepository->create(Simple::class, $portalNodeKeySource);
        $this->mappingRepository->create($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        static::assertCount(1, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));

        $payload = new MappingPersistPayload($portalNodeKeySource);
        $payload->delete($mappingNodeKey);

        $this->mappingPersister->persist($payload);

        static::assertCount(0, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));

        $payload = new MappingPersistPayload($portalNodeKeySource);
        $payload->delete($mappingNodeKey);

        $failed = false;

        try {
            $this->mappingPersister->persist($payload);
        } catch (\Throwable $t) {
            $failed = true;
        }

        if (!$failed) {
            static::fail('mappingPersister->persist should have failed');
        }
    }
}
