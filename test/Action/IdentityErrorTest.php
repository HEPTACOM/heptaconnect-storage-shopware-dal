<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\DatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityError\IdentityErrorCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityError\IdentityErrorCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Fixture\Dataset\EntityA;
use Heptacom\HeptaConnect\TestSuite\Storage\Fixture\Dataset\EntityB;
use Heptacom\HeptaConnect\TestSuite\Storage\Fixture\Dataset\EntityC;
use Heptacom\HeptaConnect\TestSuite\Storage\Fixture\Portal\PortalA\PortalA;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(IdentityErrorCreate::class)]
#[CoversClass(IdentityMap::class)]
#[CoversClass(IdentityOverview::class)]
#[CoversClass(IdentityPersist::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeCreate::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeGet::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(StorageFacade::class)]
#[CoversClass(StorageKeyGenerator::class)]
class IdentityErrorTest extends TestCase
{
    private ?PortalNodeKeyInterface $portalA = null;

    private ?IdentityMapActionInterface $identityMap = null;

    private ?IdentityErrorCreateActionInterface $identityErrorCreateAction = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $facade = new StorageFacade($this->getConnection());
        $portalNodeCreate = $facade->getPortalNodeCreateAction();
        $portalNodeGet = $facade->getPortalNodeGetAction();
        $this->identityMap = $facade->getIdentityMapAction();
        $this->identityErrorCreateAction = $facade->getIdentityErrorCreateAction();

        $createPayloads = new PortalNodeCreatePayloads([new PortalNodeCreatePayload(PortalA::class())]);
        $createResults = $portalNodeCreate->create($createPayloads);
        $getCriteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection($createResults->column('getPortalNodeKey')));

        foreach ($portalNodeGet->get($getCriteria) as $portalNode) {
            if ($portalNode->getPortalClass()->equals(PortalA::class())) {
                $this->portalA = $portalNode->getPortalNodeKey();
            }
        }

        static::assertSame($createPayloads->count(), $createResults->count());
        static::assertNotNull($this->portalA);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $portalNodeDelete = $facade->getPortalNodeDeleteAction();

        $portalNodeDelete->delete(new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([
            $this->portalA,
        ])));
        $this->portalA = null;

        parent::tearDown();
    }

    /**
     * @param class-string<DatasetEntityContract> $entityClass
     */
    #[DataProvider('provideEntityClasses')]
    public function testCreateNestedErrorMessage(string $entityClass): void
    {
        /** @var DatasetEntityContract $entity */
        $entity = new $entityClass();
        $entity->setPrimaryKey('57945df7-b8c8-4cca-a92e-53b71e8753ad');

        $identityMapResult = $this->identityMap->map(new IdentityMapPayload($this->portalA, new DatasetEntityCollection([
            $entity,
        ])));

        static::assertSame(1, $identityMapResult->getMappedDatasetEntityCollection()->count());

        /** @var MappedDatasetEntityStruct|null $mappedEntity */
        $mappedEntity = $identityMapResult->getMappedDatasetEntityCollection()->first();

        static::assertInstanceOf(MappedDatasetEntityStruct::class, $mappedEntity);

        $oldCount = (int) $this->getConnection()->fetchColumn('SELECT COUNT(1) FROM heptaconnect_mapping_error_message');

        $this->identityErrorCreateAction->create(new IdentityErrorCreatePayloads([
            new IdentityErrorCreatePayload(
                new MappingComponentStruct($this->portalA, $entityClass::class(), $entity->getPrimaryKey()),
                new \LogicException(
                    'This does not work properly',
                    123,
                    new \LogicException('This does not work either')
                )
            ),
        ]));

        $newCount = (int) $this->getConnection()->fetchColumn('SELECT COUNT(1) FROM heptaconnect_mapping_error_message');

        static::assertSame($newCount, $oldCount + 2);
    }

    /**
     * @param class-string<DatasetEntityContract> $entityClass
     */
    #[DataProvider('provideEntityClasses')]
    public function testFailCreateErrorWhenMappingNodeDoesNotExist(string $entityClass): void
    {
        /** @var DatasetEntityContract $entity */
        $entity = new $entityClass();
        $entity->setPrimaryKey('b85d0182-4392-4c81-bb77-411be927ca39');

        $oldCount = (int) $this->getConnection()->fetchColumn('SELECT COUNT(1) FROM heptaconnect_mapping_error_message');

        try {
            $this->identityErrorCreateAction->create(new IdentityErrorCreatePayloads([
                new IdentityErrorCreatePayload(
                    new MappingComponentStruct($this->portalA, $entityClass::class(), $entity->getPrimaryKey()),
                    new \LogicException(
                        'This does not work properly',
                        123,
                        new \LogicException('This does not work either')
                    )
                ),
            ]));
            static::fail();
        } catch (CreateException) {
        }

        $newCount = (int) $this->getConnection()->fetchColumn('SELECT COUNT(1) FROM heptaconnect_mapping_error_message');

        static::assertSame($newCount, $oldCount);
    }

    public static function provideEntityClasses(): iterable
    {
        yield [EntityA::class];
        yield [EntityB::class];
        yield [EntityC::class];
    }
}
