<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\RouteTestContract;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class RouteTest extends RouteTestContract
{
    private const PORTAL_A = 'bcfafd2f6b934dc89ee7309f7b7f2759';

    private const PORTAL_B = 'da25423dde3d446884fd063ad0d54cf3';

    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);
        $now = (new DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $connection->insert('heptaconnect_portal_node', [
            'id' => \hex2bin(self::PORTAL_A),
            'class_name' => self::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
        $connection->insert('heptaconnect_portal_node', [
            'id' => \hex2bin(self::PORTAL_B),
            'class_name' => TestCase::class,
            'created_at' => $now,
        ], ['id' => Types::BINARY]);
    }

    protected function createRouteCreateAction(): RouteCreateActionInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);
        /** @var EntityRepositoryInterface $entityTypeRepository */
        $entityTypeRepository = $kernel->getContainer()->get('heptaconnect_entity_type.repository');
        $storageKeyGenerator = new StorageKeyGenerator();
        $entityTypes = new EntityTypeAccessor($entityTypeRepository);
        $routeCapabilities = new RouteCapabilityAccessor($connection);

        return new RouteCreate($connection, $storageKeyGenerator, $entityTypes, $routeCapabilities);
    }

    protected function createRouteFindAction(): RouteFindActionInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);

        return new RouteFind($connection);
    }

    protected function createRouteGetAction(): RouteGetActionInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);
        $iterator = new QueryIterator();

        return new RouteGet($connection, $iterator);
    }

    protected function createReceptionRouteListAction(): ReceptionRouteListActionInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);
        $iterator = new QueryIterator();

        return new ReceptionRouteList($connection, $iterator);
    }

    protected function getPortalNodeA(): PortalNodeKeyInterface
    {
        return new PortalNodeStorageKey(self::PORTAL_A);
    }

    protected function getPortalNodeB(): PortalNodeKeyInterface
    {
        return new PortalNodeStorageKey(self::PORTAL_B);
    }
}
