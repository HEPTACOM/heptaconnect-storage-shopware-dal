<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 */
class PortalNodeGetTest extends TestCase
{
    private const PORTAL_A = 'b43cbc506680462c8a50513fa02032a6';

    private const PORTAL_B = '4632d49df5d4430f9b498ecd44cc7c58';

    private const PORTAL_DELETED = '48f0cb70cdce4085953e9608d584b097';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalA = Uuid::fromHexToBytes(self::PORTAL_A);
        $portalB = Uuid::fromHexToBytes(self::PORTAL_B);
        $portalDeleted = Uuid::fromHexToBytes(self::PORTAL_DELETED);
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $yesterday = \date_create()->sub(new \DateInterval('P1D'))->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $tomorrow = \date_create()->add(new \DateInterval('P1D'))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalA,
            'class_name' => TestCase::class,
            'created_at' => $yesterday,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalB,
            'class_name' => self::class,
            'created_at' => $tomorrow,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalDeleted,
            'class_name' => self::class,
            'created_at' => $now,
            'deleted_at' => $now,
        ], ['id' => Types::BINARY]);
    }

    public function testDeletedAt(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $action = new PortalNodeGet($connection, new QueryIterator());
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL_DELETED)]));

        static::assertCount(0, $action->get($criteria));
    }

    public function testGet(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeGet($connection, new QueryIterator());
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL_A)]));

        /** @var \Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetResult $item */
        foreach ($action->get($criteria) as $item) {
            static::assertSame(Simple::class, $item->getPortalClass());
        }
    }
}
