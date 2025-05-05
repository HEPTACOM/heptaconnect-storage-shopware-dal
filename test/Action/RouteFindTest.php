<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteFind::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class RouteFindTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'alias' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'name' => self::class,
            'created_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Id::randomBinary(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => $now,
            'deleted_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getRouteFindAction();
        $criteria = new RouteFindCriteria(
            new PortalNodeStorageKey($portalNodeHex),
            new PortalNodeStorageKey($portalNodeHex),
            (new class() extends DatasetEntityContract {
            })::class()
        );
        static::assertNull($action->find($criteria));
    }

    public function testFind(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $portalNodeHex = Id::toHex($portalNode);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'alias' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $entityType = Id::randomBinary();
        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'name' => self::class,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);
        $connection->insert('heptaconnect_route', [
            'id' => Id::randomBinary(),
            'type_id' => $entityType,
            'source_id' => $portalNode,
            'target_id' => $portalNode,
            'created_at' => DateTime::nowToStorage(),
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = new StorageFacade($connection);
        $action = $facade->getRouteFindAction();
        $criteria = new RouteFindCriteria(
            new PortalNodeStorageKey($portalNodeHex),
            new PortalNodeStorageKey($portalNodeHex),
            new UnsafeClassString(self::class)
        );
        static::assertInstanceOf(RouteFindResult::class, $action->find($criteria));
    }
}
