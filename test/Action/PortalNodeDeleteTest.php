<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\StorageFacadeProvider;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractStorageKey::class)]
#[CoversClass(DateTime::class)]
#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(PortalNodeDelete::class)]
#[CoversClass(PortalNodeStorageKey::class)]
#[CoversClass(QueryBuilder::class)]
#[CoversClass(QueryFactory::class)]
#[CoversClass(QueryIterator::class)]
#[CoversClass(SelectQueryBuilder::class)]
#[CoversClass(StorageFacade::class)]
class PortalNodeDeleteTest extends TestCase
{
    private const string PORTAL = '4632d49df5d4430f9b498ecd44cc7c58';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $portal = Id::toBinary(self::PORTAL);

        $this->getConnection()->insert('heptaconnect_portal_node', [
            'id' => $portal,
            'class_name' => self::class,
            'alias' => self::class,
            'configuration' => '{}',
            'created_at' => DateTime::nowToStorage(),
        ], ['id' => Types::BINARY]);
    }

    public function testDelete(): void
    {
        $connection = $this->getConnection();
        $facade = StorageFacadeProvider::createContainerStorageFacade($connection);

        static::assertEquals(1, $connection->fetchOne('SELECT COUNT(1) FROM heptaconnect_portal_node WHERE deleted_at IS NULL'));

        $action = $facade->getPortalNodeDeleteAction();
        $criteria = new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL)]));
        $action->delete($criteria);

        static::assertEquals(0, $connection->fetchOne('SELECT COUNT(1) FROM heptaconnect_portal_node WHERE deleted_at IS NULL'));
    }
}
