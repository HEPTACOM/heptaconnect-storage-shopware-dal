<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\PortalNodeTestContract;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class PortalNodeTest extends PortalNodeTestContract
{
    protected function createStorageFacade(): StorageFacadeInterface
    {
        $kernel = $this->kernel;
        /** @var Connection $connection */
        $connection = $kernel->getContainer()->get(Connection::class);
        /** @var EntityRepositoryInterface $entityTypeRepository */
        $entityTypeRepository = $kernel->getContainer()->get('heptaconnect_entity_type.repository');

        return new StorageFacade($connection, $entityTypeRepository);
    }
}
