<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589662318CreateDatasetEntityTypeTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589673188CreateMappingNodeTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589674916CreateMappingTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590070312CreateRouteTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590250578CreateErrorMessageTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1595776348AddWebhookTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596457486AddCronjobTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596472471AddCronjobRunTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596939935CreatePortalNodeKeyValueStorageTable;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Bundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migration1589662318CreateDatasetEntityTypeTable::class)]
#[CoversClass(Migration1589673188CreateMappingNodeTable::class)]
#[CoversClass(Migration1589674916CreateMappingTable::class)]
#[CoversClass(Migration1590070312CreateRouteTable::class)]
#[CoversClass(Migration1590250578CreateErrorMessageTable::class)]
#[CoversClass(Migration1595776348AddWebhookTable::class)]
#[CoversClass(Migration1596457486AddCronjobTable::class)]
#[CoversClass(Migration1596472471AddCronjobRunTable::class)]
#[CoversClass(Migration1596939935CreatePortalNodeKeyValueStorageTable::class)]
class ShopwareIntegrationTest extends TestCase
{
    protected Fixture\ShopwareKernel $kernel;

    #[\Override]
    protected function setUp(): void
    {
        $this->kernel = new Fixture\ShopwareKernel(Fixture\ShopwareKernel::getConnection());
        $this->kernel->boot();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testShopwareKernelLoading(): void
    {
        $this->kernel->registerBundles();
        $bundle = $this->kernel->getBundle('FixtureBundleForIntegration');

        static::assertInstanceOf(Bundle::class, $bundle);
    }
}
