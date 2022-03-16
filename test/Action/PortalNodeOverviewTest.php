<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 */
class PortalNodeOverviewTest extends TestCase
{
    private const PORTAL_FIRST_CREATED = 'b43cbc506680462c8a50513fa02032a6';

    private const PORTAL_LAST_CREATED = '4632d49df5d4430f9b498ecd44cc7c58';

    private const PORTAL_DELETED = '48f0cb70cdce4085953e9608d584b097';

    protected bool $setupQueryTracking = false;

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalFirstCreated = Uuid::fromHexToBytes(self::PORTAL_FIRST_CREATED);
        $portalLastCreated = Uuid::fromHexToBytes(self::PORTAL_LAST_CREATED);
        $portalDeleted = Uuid::fromHexToBytes(self::PORTAL_DELETED);
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $yesterday = \date_create()->sub(new \DateInterval('P1D'))->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $tomorrow = \date_create()->add(new \DateInterval('P1D'))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalFirstCreated,
            'class_name' => TestCase::class,
            'configuration' => '{}',
            'created_at' => $yesterday,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => $portalLastCreated,
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
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        static::assertCount(2, $action->overview($criteria));
    }

    public function testPagination(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria0 = new PortalNodeOverviewCriteria();
        $criteria0->setPageSize(1);
        $criteria0->setPage(0);

        $criteria1 = clone $criteria0;
        $criteria1->setPage(1);

        $criteria2 = clone $criteria0;
        $criteria2->setPage(4);

        static::assertCount(1, $action->overview($criteria0));
        static::assertCount(1, $action->overview($criteria1));
        static::assertCount(0, $action->overview($criteria2));
    }

    public function testSortByDateAsc(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        $criteria->setSort([
            PortalNodeOverviewCriteria::FIELD_CREATED => PortalNodeOverviewCriteria::SORT_ASC,
        ]);

        /** @var PortalNodeOverviewResult $item */
        foreach ($action->overview($criteria) as $item) {
            static::assertTrue($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_FIRST_CREATED)));

            break;
        }
    }

    public function testSortByDateDesc(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        $criteria->setSort([
            PortalNodeOverviewCriteria::FIELD_CREATED => PortalNodeOverviewCriteria::SORT_DESC,
        ]);

        /** @var PortalNodeOverviewResult $item */
        foreach ($action->overview($criteria) as $item) {
            static::assertTrue($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_LAST_CREATED)));

            break;
        }
    }

    public function testSortByClassNameAsc(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        $criteria->setSort([
            PortalNodeOverviewCriteria::FIELD_CLASS_NAME => PortalNodeOverviewCriteria::SORT_ASC,
        ]);

        $indexA = null;
        $indexB = null;

        /** @var PortalNodeOverviewResult $item */
        foreach ($action->overview($criteria) as $index => $item) {
            if ($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_LAST_CREATED))) {
                $indexA = $index;
            }

            if ($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_FIRST_CREATED))) {
                $indexB = $index;
            }
        }

        static::assertGreaterThan($indexA, $indexB);
    }

    public function testSortByClassNameDesc(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        $criteria->setSort([
            PortalNodeOverviewCriteria::FIELD_CLASS_NAME => PortalNodeOverviewCriteria::SORT_DESC,
        ]);

        $indexA = null;
        $indexB = null;

        /** @var PortalNodeOverviewResult $item */
        foreach ($action->overview($criteria) as $index => $item) {
            if ($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_LAST_CREATED))) {
                $indexA = $index;
            }

            if ($item->getPortalNodeKey()->equals(new PortalNodeStorageKey(self::PORTAL_FIRST_CREATED))) {
                $indexB = $index;
            }
        }

        static::assertGreaterThan($indexB, $indexA);
    }

    public function testFilterPortalNodeClass(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $action = new PortalNodeOverview($connection);
        $criteria = new PortalNodeOverviewCriteria();
        $criteria->setClassNameFilter([TestCase::class]);

        static::assertCount(1, $action->overview($criteria));

        /** @var PortalNodeOverviewResult $item */
        foreach ($action->overview($criteria) as $item) {
            static::assertSame(TestCase::class, $item->getPortalClass());
        }
    }
}
