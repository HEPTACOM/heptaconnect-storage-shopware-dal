<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Get\JobGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
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

        $connection = $this->kernel->getContainer()->get(Connection::class);
        $entityType = Uuid::fromHexToBytes(self::ENTITY_TYPE);
        $jobType = Uuid::fromHexToBytes(self::JOB_TYPE);
        $portal = Uuid::fromHexToBytes(self::PORTAL);
        $job = Uuid::fromHexToBytes(self::JOB);
        $jobPayload = Uuid::randomBytes();
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_job_type', [
            'id' => $jobType,
            'type' => 'act',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portal,
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_job_payload', [
            'id' => $jobPayload,
            'payload' => \gzcompress(\serialize([
                'foo' => 'bar',
            ])),
            'format' => 'serialized+gzpress',
            'created_at' => $now,
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
            'created_at' => $now,
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
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new JobGet($connection, new QueryIterator());
        $criteria = new JobGetCriteria(new JobKeyCollection([new JobStorageKey(self::JOB)]));
        $count = 0;

        /** @var \Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Get\JobGetResult $item */
        foreach ($action->get($criteria) as $item) {
            ++$count;
            static::assertSame(Simple::class, $item->getMappingComponent()->getEntityType());
            static::assertSame('123', $item->getMappingComponent()->getExternalId());
            static::assertSame([
                'foo' => 'bar',
            ], $item->getPayload());
        }

        static::assertSame(1, $count);
    }
}
