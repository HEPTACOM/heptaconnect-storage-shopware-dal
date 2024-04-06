<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture;

use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\Kernel;
use Shopware\Core\System\Language\CachedLanguageLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ShopwareKernel extends Kernel
{
    public function __construct()
    {
        /** @var \Composer\Autoload\ClassLoader $classLoader */
        $classLoader = require __DIR__ . '/../../vendor/autoload.php';

        parent::__construct(
            'prod',
            true,
            new StaticKernelPluginLoader($classLoader),
            'prod',
            self::SHOPWARE_FALLBACK_VERSION,
            null,
            __DIR__ . '/ShopwareProject'
        );
    }

    public function getCacheDir(): string
    {
        return \sprintf(
            '%s/var/cache/%s_h%s',
            __DIR__ . '/../../.build/ShopwareProject',
            $this->getEnvironment(),
            $this->getCacheHash()
        );
    }

    public function getLogDir(): string
    {
        return __DIR__ . '/../../.build/ShopwareProject/var/log';
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->getDefinition(CachedLanguageLoader::class)->setPublic(true);
    }
}
