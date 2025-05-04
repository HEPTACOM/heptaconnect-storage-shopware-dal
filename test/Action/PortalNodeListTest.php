<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeList::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class PortalNodeListTest extends TestCase
{
    public function testDeletedAt(): void
    {
        $connection = $this->getConnection();
        $portalNode = Id::randomBinary();
        $now = DateTime::nowToStorage();

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalNode,
            'class_name' => self::class,
            'configuration' => '{}',
            'created_at' => $now,
            'deleted_at' => $now,
        ], [
            'id' => Types::BINARY,
        ]);

        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
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

        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);
        $action = $facade->getPortalNodeListAction();
        $resultItems = \iterable_to_array($action->list());
        static::assertCount(1, $resultItems);
    }
}
