<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class JobGetTest extends TestCase
{
    private const ENTITY_TYPE = 'c6aad9f6355b4bf78f548a73caa502aa';

    private const JOB_TYPE = '448dc638a1304864b0c66935dafe1b6e';

    private const PORTAL = '4632d49df5d4430f9b498ecd44cc7c58';

    private const JOB = '4e836953e1eb4916b4410b9af2b9b2f9';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $entityType = Id::toBinary(self::ENTITY_TYPE);
        $jobType = Id::toBinary(self::JOB_TYPE);
        $portal = Id::toBinary(self::PORTAL);
        $job = Id::toBinary(self::JOB);
        $jobPayload = Id::randomBinary();

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => Simple::class,
            'created_at' => DateTime::nowToStorage(),
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_job_type', [
            'id' => $jobType,
            'type' => 'act',
            'created_at' => DateTime::nowToStorage(),
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portal,
            'configuration' => '{}',
            'class_name' => self::class,
            'created_at' => DateTime::nowToStorage(),
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_job_payload', [
            'id' => $jobPayload,
            'payload' => \gzcompress(\serialize([
                'foo' => 'bar',
            ])),
            'format' => 'serialized+gzpress',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
            'payload' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_job', [
            'id' => $job,
            'external_id' => '123',
            'portal_node_id' => $portal,
            'entity_type_id' => $entityType,
            'job_type_id' => $jobType,
            'payload_id' => $jobPayload,
            'state_id' => JobStateEnum::open(),
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
            'state_id' => Types::BINARY,
            'portal_node_id' => Types::BINARY,
            'entity_type_id' => Types::BINARY,
            'job_type_id' => Types::BINARY,
            'payload_id' => Types::BINARY,
        ]);
    }

    public function testGet(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getJobGetAction();
        $criteria = new JobGetCriteria(new JobKeyCollection([new JobStorageKey(self::JOB)]));
        $count = 0;

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Job\Get\JobGetResult $item */
        foreach ($action->get($criteria) as $item) {
            ++$count;
            static::assertTrue(Simple::class()->equals($item->getMappingComponent()->getEntityType()));
            static::assertSame('123', $item->getMappingComponent()->getExternalId());
            static::assertSame([
                'foo' => 'bar',
            ], $item->getPayload());
        }

        static::assertSame(1, $count);
    }
}
