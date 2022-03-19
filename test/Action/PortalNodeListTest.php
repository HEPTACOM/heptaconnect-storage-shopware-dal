<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class PortalNodeListTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
            'deleted_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getPortalNodeListAction();
        $resultItems = \iterable_to_array($action->list());
        static::assertCount(0, $resultItems);
    }

    public function testNormal(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getPortalNodeListAction();
        $resultItems = \iterable_to_array($action->list());
        static::assertCount(1, $resultItems);
    }
}
