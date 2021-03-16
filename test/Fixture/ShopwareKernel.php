<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture;

use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\Kernel;

class ShopwareKernel extends Kernel
{
    public function __construct()
    {
        /** @var \Composer\Autoload\ClassLoader $classLoader */
        $classLoader = require __DIR__.'/../../vendor/autoload.php';

        parent::__construct(
            'prod',
            true,
            new StaticKernelPluginLoader($classLoader),
            'prod',
            self::SHOPWARE_FALLBACK_VERSION,
            null,
            __DIR__.'/ShopwareProject'
        );
    }
}
