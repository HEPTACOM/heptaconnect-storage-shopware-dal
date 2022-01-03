<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappingComponentStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayloads;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum
 */
class JobCreateTest extends TestCase
{
    protected bool $setupQueryTracking = false;

    public function testCreate(): void
    {
        $source = Uuid::randomBytes();
        $entityType = Uuid::randomBytes();
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $source,
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $sourceHex = Uuid::fromBytesToHex($source);

        /** @var EntityRepositoryInterface $entityTypes */
        $entityTypes = $this->kernel->getContainer()->get('heptaconnect_entity_type.repository');

        $action = new JobCreate($connection, new StorageKeyGenerator(), new JobTypeAccessor($connection), new EntityTypeAccessor($entityTypes));
        $action->create(new JobCreatePayloads([
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class, '1'), [
                'party' => 'people',
            ]),
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class, '2'), [
                'party' => 'people',
            ]),
            new JobCreatePayload('foobar', new MappingComponentStruct(new PortalNodeStorageKey($sourceHex), Simple::class, '3'), null),
        ]));

        $count = (int) $connection->executeQuery('SELECT count(1) FROM `heptaconnect_job`')->fetchColumn();
        static::assertSame(3, $count);
        $count = (int) $connection->executeQuery('SELECT count(1) FROM `heptaconnect_job_payload`')->fetchColumn();
        static::assertSame(1, $count);
    }
}
