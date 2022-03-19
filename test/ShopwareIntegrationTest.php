<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Bundle;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589662318CreateDatasetEntityTypeTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589673188CreateMappingNodeTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589674916CreateMappingTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590070312CreateRouteTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590250578CreateErrorMessageTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1595776348AddWebhookTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596457486AddCronjobTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596472471AddCronjobRunTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596939935CreatePortalNodeKeyValueStorageTable
 */
class ShopwareIntegrationTest extends TestCase
{
    protected Fixture\ShopwareKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Fixture\ShopwareKernel();
        $this->kernel->boot();
    }

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
