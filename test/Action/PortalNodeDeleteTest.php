<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class PortalNodeDeleteTest extends TestCase
{
    private const PORTAL = '4632d49df5d4430f9b498ecd44cc7c58';

    protected function setUp(): void
    {
        parent::setUp();

        $portal = Id::toBinary(self::PORTAL);

        $this->getConnection()->insert('heptaconnect_portal_node', [
            'id' => $portal,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], ['id' => Types::BINARY]);
    }

    public function testDelete(): void
    {
        $connection = $this->getConnection();
        $facade = new StorageFacade($connection);

        static::assertEquals(1, $connection->fetchColumn('SELECT COUNT(1) FROM heptaconnect_portal_node WHERE deleted_at IS NULL'));

        $action = $facade->getPortalNodeDeleteAction();
        $criteria = new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL)]));
        $action->delete($criteria);

        static::assertEquals(0, $connection->fetchColumn('SELECT COUNT(1) FROM heptaconnect_portal_node WHERE deleted_at IS NULL'));
    }
}
