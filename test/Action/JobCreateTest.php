<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayloads;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class JobCreateTest extends TestCase
{
    public function testCreate(): void
    {
        $source = Id::randomBinary();
        $entityType = Id::randomBinary();
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => Simple::class,
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

        $count = (int) $connection->executeQuery('SELECT count(1) FROM `heptaconnect_job`')->fetchColumn();
        static::assertSame(3, $count);
        $count = (int) $connection->executeQuery('SELECT count(1) FROM `heptaconnect_job_payload`')->fetchColumn();
        static::assertSame(1, $count);
    }
}
