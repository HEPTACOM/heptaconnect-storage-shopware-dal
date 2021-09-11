<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister
 */
class MappingPersisterTest extends TestCase
{
    protected ShopwareKernel $kernel;

    private MappingPersister $mappingPersister;

    private PortalNodeRepository $portalNodeRepository;

    private MappingNodeRepository $mappingNodeRepository;

    private MappingRepository $mappingRepository;

    protected function setUp(): void
    {
        $this->kernel = new ShopwareKernel();
        $this->kernel->boot();

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
            ->getRepository('heptaconnect_dataset_entity_type');

        $this->mappingPersister = new MappingPersister($shopwareMappingRepository);
        $storageKeyGenerator = new StorageKeyGenerator();
        $contextFactory = new ContextFactory();
        $this->portalNodeRepository = new PortalNodeRepository(
            $shopwarePortalNodeRepository,
            $storageKeyGenerator,
            $contextFactory
        );
        $datasetEntityTypeAccessor = new DatasetEntityTypeAccessor($shopwareDatasetEntityTypeRepository);
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
    }

    protected function tearDown(): void
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->rollBack();
        $this->kernel->shutdown();
    }

    public function testPersistingSingleEntityMapping()
    {
        $externalIdSource = 'a1f2b3b52f234bfab4fb570ff2f9d174';
        $externalIdTarget = 'c7791ca6c13e42b58d1f09368b34647e';

        $portalNodeKeySource = $this->portalNodeRepository->create(PortalContract::class);
        $portalNodeKeyTarget = $this->portalNodeRepository->create(PortalContract::class);

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

        self::assertCount(1, $targetMappings);

        /** @var MappingKeyInterface $targetMappingKey */
        $targetMappingKey = \array_shift($targetMappings);
        $targetMapping = $this->mappingRepository->read($targetMappingKey);

        self::assertTrue($targetMapping->getMappingNodeKey()->equals($mappingNodeKey));
        self::assertTrue($targetMapping->getPortalNodeKey()->equals($portalNodeKeyTarget));
        self::assertSame($externalIdTarget, $targetMapping->getExternalId());
        self::assertSame(Simple::class, $targetMapping->getDatasetEntityClassName());
    }

    public function testPersistingSameExternalIdToTwoDifferentMappingNodes()
    {
        $externalId1Source = 'a1f2b3b52f234bfab4fb570ff2f9d174';
        $externalId2Source = 'ffb41aec6bcb4ca8a5a9e8a2631d632a';
        $externalIdTarget = 'c7791ca6c13e42b58d1f09368b34647e';

        $portalNodeKeySource = $this->portalNodeRepository->create(PortalContract::class);
        $portalNodeKeyTarget = $this->portalNodeRepository->create(PortalContract::class);

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
            self::fail('mappingPersister->persist should have failed');
        }

        $target1Mappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey1),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        self::assertCount(0, $target1Mappings);
        $target2Mappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey2),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        self::assertCount(0, $target2Mappings);
    }

    public function testCreatingDifferentExternalIdToTwoSameMappingNodes()
    {
        $externalIdSource = 'a1f2b3b52f234bfab4fb570ff2f9d174';
        $externalId1Target = '686a849649134e1dbebc05830981bc86';
        $externalId2Target = 'c7791ca6c13e42b58d1f09368b34647e';

        $portalNodeKeySource = $this->portalNodeRepository->create(PortalContract::class);
        $portalNodeKeyTarget = $this->portalNodeRepository->create(PortalContract::class);

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
            self::fail('mappingPersister->persist should have failed');
        }

        $targetMappings = \iterable_to_array(\iterable_filter(
            $this->mappingRepository->listByMappingNode($mappingNodeKey),
            fn (MappingKeyInterface $mappingKey) => $this->mappingRepository
                ->read($mappingKey)
                ->getPortalNodeKey()
                ->equals($portalNodeKeyTarget)
        ));
        self::assertCount(0, $targetMappings);
    }
}
