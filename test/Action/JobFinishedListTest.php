<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class JobFinishedListTest extends TestCase
{
    private const ENTITY_TYPE = 'c6aad9f6355b4bf78f548a73caa502aa';

    private const JOB_TYPE = '448dc638a1304864b0c66935dafe1b6e';

    private const PORTAL = '4632d49df5d4430f9b498ecd44cc7c58';

    private const JOB_NOT_FINISHED = '4e836953e1eb4916b4410b9af2b9b2f9';

    private const JOB_FINISHED = '66d4a9d9f8724af1bcfec62fa3c54cdd';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $entityType = Id::toBinary(self::ENTITY_TYPE);
        $jobType = Id::toBinary(self::JOB_TYPE);
        $portal = Id::toBinary(self::PORTAL);
        $jobFinished = Id::toBinary(self::JOB_FINISHED);
        $jobNotFinished = Id::toBinary(self::JOB_NOT_FINISHED);
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
            'id' => $jobNotFinished,
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
        $connection->insert('heptaconnect_job', [
            'id' => $jobFinished,
            'external_id' => '123',
            'portal_node_id' => $portal,
            'entity_type_id' => $entityType,
            'job_type_id' => $jobType,
            'payload_id' => $jobPayload,
            'state_id' => JobStateEnum::finished(),
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

    public function testList(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getJobListFinishedAction();
        $count = 0;
        $finishedJobKey = new JobStorageKey(self::JOB_FINISHED);

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Job\Listing\JobListFinishedResult $item */
        foreach ($action->list() as $item) {
            ++$count;
            static::assertTrue($item->getJobKey()->equals($finishedJobKey));
        }

        static::assertSame(1, $count);
    }
}
