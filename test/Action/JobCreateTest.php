<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayloads;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(JobCreate::class)]
#[CoversClass(JobStateEnum::class)]
#[CoversClass(JobTypeAccessor::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class JobCreateTest extends TestCase
{
    public function testCreate(): void
    {
        $source = Id::randomBinary();
        $entityType = Id::randomBinary();
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'name' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $source,
            'configuration' => '{}',
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $sourceHex = Id::toHex($source);
        $action = $facade->getJobCreateAction();
        $action->create(new JobCreatePayloads([
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class(), '1'), [
                'party' => 'people',
            ]),
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class(), '2'), [
                'party' => 'people',
            ]),
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class(), '3'), null),
        ]));

        $count = (int) $connection->fetchOne('SELECT count(1) FROM `heptaconnect_job`');
        static::assertSame(3, $count);
        $count = (int) $connection->fetchOne('SELECT count(1) FROM `heptaconnect_job_payload`');
        static::assertSame(1, $count);
    }
}
