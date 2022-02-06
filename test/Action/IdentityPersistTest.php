<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\MappingNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistDeletePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistUpdatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist
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
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 */
class IdentityPersistTest extends TestCase
{
    private IdentityPersist $identityPersistAction;

    private MappingRepository $mappingRepository;

    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    private IdentityOverviewActionInterface $identityOverviewAction;

    private StorageKeyGenerator $storageKeyGenerator;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->kernel->getContainer()->get(Connection::class);

        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);
        $shopwareMappingRepository = $definitionInstanceRegistry
            ->getRepository('heptaconnect_mapping');

        $this->identityPersistAction = new IdentityPersist($this->connection);
        $this->storageKeyGenerator = new StorageKeyGenerator();
        $contextFactory = new ContextFactory();
        $this->datasetEntityTypeAccessor = new EntityTypeAccessor($this->connection);
        $this->mappingRepository = new MappingRepository(
            $this->storageKeyGenerator,
            $shopwareMappingRepository,
            $contextFactory
        );
        $this->portalNodeCreateAction = new PortalNodeCreate($this->connection, $this->storageKeyGenerator);
        $this->identityOverviewAction = new IdentityOverview($this->connection);
    }

    public function testMergingMappingNodes(): void
    {
        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));

        $externalIdSource = (string) Uuid::uuid4()->getHex();
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $mappingNodeKeySource = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKeySource, $externalIdSource);

        $externalIdTarget = (string) Uuid::uuid4()->getHex();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();
        $mappingNodeKeyTarget = $this->createMappingNode(Simple::class, $portalNodeKeyTarget);
        $this->createMapping($portalNodeKeyTarget, $mappingNodeKeyTarget, $externalIdTarget);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistCreatePayload($mappingNodeKeySource, $externalIdTarget),
        ]));

        static::assertCount(1, $this->listByMappingNode($mappingNodeKeySource));
        static::assertCount(1, $this->listByMappingNode($mappingNodeKeyTarget));

        $this->identityPersistAction->persist($payload);

        static::assertCount(0, $this->listByMappingNode($mappingNodeKeySource));
        static::assertCount(2, $this->listByMappingNode($mappingNodeKeyTarget));
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

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistCreatePayload($mappingNodeKey, $externalIdTarget),
        ]));

        $this->identityPersistAction->persist($payload);

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

        $mappingNodeKey1 = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $mappingNodeKey2 = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey1, $externalId1Source);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey2, $externalId2Source);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistCreatePayload($mappingNodeKey1, $externalIdTarget),
            new IdentityPersistCreatePayload($mappingNodeKey2, $externalIdTarget),
        ]));

        $failed = false;

        try {
            $this->identityPersistAction->persist($payload);
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

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistCreatePayload($mappingNodeKey, $externalId1Target),
            new IdentityPersistCreatePayload($mappingNodeKey, $externalId2Target),
        ]));

        $failed = false;

        try {
            $this->identityPersistAction->persist($payload);
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

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistCreatePayload($mappingNodeKey, $externalId1Target),
            new IdentityPersistUpdatePayload($mappingNodeKey, $externalId2Target),
        ]));

        $failed = false;

        try {
            $this->identityPersistAction->persist($payload);
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

        $mappingNodeKeyA = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $mappingNodeKeyB = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKeyA, $externalIdSourceA);
        $this->createMapping($portalNodeKeySource, $mappingNodeKeyB, $externalIdSourceB);
        $this->createMapping($portalNodeKeyTarget, $mappingNodeKeyA, $externalIdTargetA);
        $this->createMapping($portalNodeKeyTarget, $mappingNodeKeyB, $externalIdTargetB);

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistUpdatePayload($mappingNodeKeyA, $externalIdTargetB),
            new IdentityPersistUpdatePayload($mappingNodeKeyB, $externalIdTargetA),
        ]));

        $this->identityPersistAction->persist($payload);

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

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);
        $this->createMapping($portalNodeKeyTarget, $mappingNodeKey, $externalIdTarget);

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

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

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

        $payload = new IdentityPersistPayload($portalNodeKeySource, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

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

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        static::assertCount(1, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));

        $payload = new IdentityPersistPayload($portalNodeKeySource, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

        static::assertCount(0, \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeySource)
        )));

        $payload = new IdentityPersistPayload($portalNodeKeySource, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $failed = false;

        try {
            $this->identityPersistAction->persist($payload);
        } catch (\Throwable $t) {
            $failed = true;
        }

        if (!$failed) {
            static::fail('mappingPersister->persist should have failed');
        }
    }

    /**
     * @return IdentityOverviewResult[]
     */
    private function listByMappingNode(MappingNodeKeyInterface $mappingNodeKey): array
    {
        $criteria = new IdentityOverviewCriteria();
        $criteria->getMappingNodeKeyFilter()->push([$mappingNodeKey]);

        return \iterable_to_array($this->identityOverviewAction->overview($criteria));
    }

    private function createMappingNode(string $entityType, PortalNodeKeyInterface $portalNodeKey): MappingNodeStorageKey
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = (new MappingNodeKeyCollection($this->storageKeyGenerator->generateKeys(MappingNodeKeyInterface::class, 1)))->first();
        $typeIds = $this->datasetEntityTypeAccessor->getIdsForTypes([$entityType]);

        if (!$result instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($result));
        }

        $this->connection->insert('heptaconnect_mapping_node', [
            'id' => \hex2bin($result->getUuid()),
            'origin_portal_node_id' => \hex2bin($portalNodeKey->getUuid()),
            'type_id' => \hex2bin($typeIds[$entityType]),
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
            'origin_portal_node_id' => Types::BINARY,
            'type_id' => Types::BINARY,
        ]);

        return $result;
    }

    private function createMapping(
        PortalNodeStorageKey $portalNodeKey,
        MappingNodeStorageKey $mappingNodeKey,
        string $externalId
    ): void {
        $key = $this->storageKeyGenerator->generateKey(MappingKeyInterface::class);

        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->connection->insert('heptaconnect_mapping', [
            'id' => \hex2bin($key->getUuid()),
            'mapping_node_id' => \hex2bin($mappingNodeKey->getUuid()),
            'portal_node_id' => \hex2bin($portalNodeKey->getUuid()),
            'external_id' => $externalId,
            'created_at' => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => Types::BINARY,
            'mapping_node_id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);
    }
}
