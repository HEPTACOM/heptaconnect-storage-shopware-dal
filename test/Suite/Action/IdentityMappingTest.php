<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Suite\Action;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Reflect\IdentityReflectPayload;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\TestSuite\Storage\Action\IdentityMappingTestContract;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase
 */
class IdentityMappingTest extends IdentityMappingTestContract
{
    public function testNoDatabaseLookupsOnEmptyPayload(): void
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);

        $reflector = new IdentityReflect($connection);
        $reflector->reflect(new IdentityReflectPayload(new PortalNodeStorageKey(Uuid::randomHex()), new MappedDatasetEntityCollection()));

        static::assertSame([], $this->trackedQueries);
    }

    protected function createStorageFacade(): StorageFacadeInterface
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);

        return new StorageFacade($connection);
    }
}
