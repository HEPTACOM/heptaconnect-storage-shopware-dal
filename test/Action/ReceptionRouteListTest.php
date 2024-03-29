<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
class ReceptionRouteListTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->getConnection();
        $receptionId = $this->getReceptionCapability();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $routeId = Id::randomBinary();
        $connection->insert('heptaconnect_route', [
            'id' => $routeId,
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => $now,
            'deleted_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route_has_capability', [
            'route_id' => $routeId,
            'route_capability_id' => $receptionId,
            'created_at' => $now,
        ], [
            'route_id' => Types::BINARY,
            'route_capability_id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getReceptionRouteListAction();
        $criteria = new ReceptionRouteListCriteria(new PortalNodeStorageKey($portalNodeHex), new UnsafeClassString(DatasetEntityContract::class));
        $resultItems = \iterable_to_array($action->list($criteria));
        static::assertCount(0, $resultItems);
    }

    public function testCapability(): void
    {
        $connection = $this->getConnection();
        $receptionId = $this->getReceptionCapability();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'type' => self::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $routeId = Id::randomBinary();
        $connection->insert('heptaconnect_route', [
            'id' => $routeId,
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getReceptionRouteListAction();
        $criteria = new ReceptionRouteListCriteria(new PortalNodeStorageKey($portalNodeHex), new UnsafeClassString(self::class));
        $resultItems = \iterable_to_array($action->list($criteria));
        static::assertCount(0, $resultItems);

        $connection->insert('heptaconnect_route_has_capability', [
            'route_id' => $routeId,
            'route_capability_id' => $receptionId,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'route_id' => Types::BINARY,
            'route_capability_id' => Types::BINARY,
        ]);

        $resultItems = \iterable_to_array($action->list($criteria));
        static::assertCount(1, $resultItems);
    }

    private function getReceptionCapability(): string
    {
        return (string) $this->getConnection()->executeQuery('SELECT `id` FROM `heptaconnect_route_capability` WHERE `name` = ?', [RouteCapability::RECEPTION])->fetchColumn();
    }
}
