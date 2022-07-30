<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class PortalNodeGetTest extends TestCase
{
    private const PORTAL_A = 'b43cbc506680462c8a50513fa02032a6';

    private const PORTAL_B = '4632d49df5d4430f9b498ecd44cc7c58';

    private const PORTAL_DELETED = '48f0cb70cdce4085953e9608d584b097';

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $portalA = Id::toBinary(self::PORTAL_A);
        $portalB = Id::toBinary(self::PORTAL_B);
        $portalDeleted = Id::toBinary(self::PORTAL_DELETED);
        $yesterday = DateTime::toStorage(\date_create()->sub(new \DateInterval('P1D')));
        $tomorrow = DateTime::toStorage(\date_create()->add(new \DateInterval('P1D')));
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalA,
            'class_name' => TestCase::class,
            'configuration' => '{}',
            'created_at' => $yesterday,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalB,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $tomorrow,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalDeleted,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
            'deleted_at' => $now,
        ], ['id' => Types::BINARY]);
    }

    public function testDeletedAt(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getPortalNodeGetAction();
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL_DELETED)]));

        static::assertCount(0, $action->get($criteria));
    }

    public function testGet(): void
    {
        $facade = new StorageFacade($this->getConnection());
        $action = $facade->getPortalNodeGetAction();
        $criteria = new PortalNodeGetCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL_A)]));

        /** @var PortalNodeGetResult $item */
        foreach ($action->get($criteria) as $item) {
            static::assertTrue($item->getPortalClass()->equals(new UnsafeClassString(TestCase::class)));
        }
    }
}
