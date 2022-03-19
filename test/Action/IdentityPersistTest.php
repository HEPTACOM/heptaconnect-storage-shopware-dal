<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\MappingKeyCollection;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class IdentityPersistTest extends TestCase
{
    private IdentityPersist $identityPersistAction;

    private PortalNodeCreateActionInterface $portalNodeCreateAction;

    private IdentityOverviewActionInterface $identityOverviewAction;

    private StorageKeyGenerator $storageKeyGenerator;

    private EntityTypeAccessor $datasetEntityTypeAccessor;

    protected function setUp(): void
    {
        parent::setUp();

        $facade = new StorageFacade($this->getConnection());
        $this->identityPersistAction = $facade->getIdentityPersistAction();
        $this->storageKeyGenerator = new StorageKeyGenerator();
        $this->datasetEntityTypeAccessor = new EntityTypeAccessor($this->getConnection(), new QueryFactory($this->getConnection(), new QueryIterator(), [], 500));
        $this->portalNodeCreateAction = $facade->getPortalNodeCreateAction();
        $this->identityOverviewAction = $facade->getIdentityOverviewAction();
    }

    public function testMergingMappingNodes(): void
    {
        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));

        $externalIdSource = Id::randomHex();
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $mappingNodeKeySource = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKeySource, $externalIdSource);

        $externalIdTarget = Id::randomHex();
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
        $externalIdSource = Id::randomHex();
        $externalIdTarget = Id::randomHex();

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

        $targetMappings = $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget);

        static::assertCount(1, $targetMappings);

        /** @var IdentityOverviewResult $targetIdentity */
        $targetIdentity = \array_shift($targetMappings);

        static::assertTrue($targetIdentity->getMappingNodeKey()->equals($mappingNodeKey));
        static::assertTrue($targetIdentity->getPortalNodeKey()->equals($portalNodeKeyTarget));
        static::assertSame($externalIdTarget, $targetIdentity->getExternalId());
        static::assertSame(Simple::class, $targetIdentity->getEntityType());
    }

    public function testPersistingSameExternalIdToTwoDifferentMappingNodes(): void
    {
        $externalId1Source = Id::randomHex();
        $externalId2Source = Id::randomHex();
        $externalIdTarget = Id::randomHex();

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

        static::assertCount(0, $this->listMappingNodes($mappingNodeKey1, $portalNodeKeyTarget));
        static::assertCount(0, $this->listMappingNodes($mappingNodeKey2, $portalNodeKeyTarget));
    }

    public function testCreatingDifferentExternalIdToTwoSameMappingNodes(): void
    {
        $externalIdSource = Id::randomHex();
        $externalId1Target = Id::randomHex();
        $externalId2Target = Id::randomHex();

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

        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget));
    }

    public function testCreatingAndUpdatingDifferentExternalIdToTwoSameMappingNodes(): void
    {
        $externalIdSource = Id::randomHex();
        $externalId1Target = Id::randomHex();
        $externalId2Target = Id::randomHex();

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

        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget));
    }

    public function testSwappingExternalIdsOfTwoMappings(): void
    {
        $externalIdSourceA = Id::randomHex();
        $externalIdTargetA = Id::randomHex();

        $externalIdSourceB = Id::randomHex();
        $externalIdTargetB = Id::randomHex();

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

        $targetMappingsA = $this->listMappingNodes($mappingNodeKeyA, $portalNodeKeyTarget);
        /** @var IdentityOverviewResult $targetMappingA */
        $targetMappingA = \array_shift($targetMappingsA);
        $targetMappingsB = $this->listMappingNodes($mappingNodeKeyB, $portalNodeKeyTarget);
        /** @var IdentityOverviewResult $targetMappingB */
        $targetMappingB = \array_shift($targetMappingsB);

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
        $externalIdSource = Id::randomHex();
        $externalIdTarget = Id::randomHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();
        $portalNodeKeyTarget = $portalNodeCreateResult[1]->getPortalNodeKey();

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);
        $this->createMapping($portalNodeKeyTarget, $mappingNodeKey, $externalIdTarget);

        static::assertCount(1, $this->listMappingNodes($mappingNodeKey, $portalNodeKeySource));
        static::assertCount(1, $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget));

        $payload = new IdentityPersistPayload($portalNodeKeyTarget, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

        static::assertCount(1, $this->listMappingNodes($mappingNodeKey, $portalNodeKeySource));
        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget));

        $payload = new IdentityPersistPayload($portalNodeKeySource, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeySource));
        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeyTarget));
    }

    public function testDeletingMappingNodesTwice(): void
    {
        $externalIdSource = Id::randomHex();

        $portalNodeCreateResult = $this->portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKeySource = $portalNodeCreateResult[0]->getPortalNodeKey();

        $mappingNodeKey = $this->createMappingNode(Simple::class, $portalNodeKeySource);
        $this->createMapping($portalNodeKeySource, $mappingNodeKey, $externalIdSource);

        static::assertCount(1, $this->listMappingNodes($mappingNodeKey, $portalNodeKeySource));

        $payload = new IdentityPersistPayload($portalNodeKeySource, new IdentityPersistPayloadCollection([
            new IdentityPersistDeletePayload($mappingNodeKey),
        ]));

        $this->identityPersistAction->persist($payload);

        static::assertCount(0, $this->listMappingNodes($mappingNodeKey, $portalNodeKeySource));

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

        $this->getConnection()->insert('heptaconnect_mapping_node', [
            'id' => Id::toBinary($result->getUuid()),
            'origin_portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
            'type_id' => Id::toBinary($typeIds[$entityType]),
            'created_at' => DateTime::nowToStorage(),
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
        $key = (new MappingKeyCollection($this->storageKeyGenerator->generateKeys(MappingKeyInterface::class, 1)))->first();

        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->getConnection()->insert('heptaconnect_mapping', [
            'id' => Id::toBinary($key->getUuid()),
            'mapping_node_id' => Id::toBinary($mappingNodeKey->getUuid()),
            'portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
            'external_id' => $externalId,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
            'mapping_node_id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
        ]);
    }

    /**
     * @return IdentityOverviewResult[]
     */
    private function listMappingNodes(
        MappingNodeStorageKey $mappingNodeKey,
        PortalNodeKeyInterface $portalNodeKey
    ): array {
        $criteria = new IdentityOverviewCriteria();
        $criteria->getPortalNodeKeyFilter()->push([$portalNodeKey]);
        $criteria->getMappingNodeKeyFilter()->push([$mappingNodeKey]);

        return \iterable_to_array($this->identityOverviewAction->overview($criteria));
    }
}
