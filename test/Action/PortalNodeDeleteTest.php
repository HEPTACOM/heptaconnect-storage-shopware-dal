<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 */
class PortalNodeDeleteTest extends TestCase
{
    private const PORTAL = '4632d49df5d4430f9b498ecd44cc7c58';

    public function testDelete(): void
    {
        $connection = $this->kernel->getContainer()->get(Connection::class);

        self::assertEquals(1, $connection->fetchColumn('SELECT COUNT(1) FROM heptaconnect_portal_node'));

        $action = new PortalNodeDelete($connection);
        $criteria = new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([new PortalNodeStorageKey(self::PORTAL)]));
        $action->delete($criteria);

        self::assertEquals(0, $connection->fetchColumn('SELECT COUNT(1) FROM heptaconnect_portal_node'));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portal = Uuid::fromHexToBytes(self::PORTAL);
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_portal_node', [
            'id' => $portal,
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
    }
}
