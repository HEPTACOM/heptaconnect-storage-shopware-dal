<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(EntityTypeAccessor::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeAliasAccessor::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(RouteCreate::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class RouteCreateTest extends TestCase
{
    protected bool $setupQueryTracking = false;

    public function testCreate(): void
    {
        $source = Id::randomBinary();
        $target = Id::randomBinary();
        $entityType = Id::randomBinary();
        $now = DateTime::nowToStorage();
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);

        $connection->insert('heptaconnect_entity_type', [
            'id' => $entityType,
            'name' => Simple::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $source,
            'class_name' => self::class,
            'alias' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $target,
            'class_name' => TestCase::class,
            'alias' => TestCase::class,
            'configuration' => '{}',
            'created_at' => $now,
        ], ['id' => Types::BINARY]);

        $sourceHex = Id::toHex($source);
        $targetHex = Id::toHex($target);

        $action = $facade->getRouteCreateAction();
        \iterable_to_array($action->create(new RouteCreatePayloads([
            new RouteCreatePayload(new PortalNodeStorageKey($sourceHex), new PortalNodeStorageKey($targetHex), Simple::class(), [RouteCapability::RECEPTION]),
            new RouteCreatePayload(new PortalNodeStorageKey($targetHex), new PortalNodeStorageKey($sourceHex), Simple::class()),
        ])));

        $count = (int) $connection->fetchOne('SELECT count(1) FROM heptaconnect_route');
        static::assertSame(2, $count);
        $count = (int) $connection->fetchOne('SELECT count(1) FROM heptaconnect_route_configuration');
        static::assertSame(1, $count);
    }
}
