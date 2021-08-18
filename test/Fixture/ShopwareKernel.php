<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture;

use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        parent::configureContainer($container, $loader);
        // Enables CSRF to fix 'The service "Shopware\Storefront\Framework\Csrf\CsrfPlaceholderHandler" has a dependency on a non-existent service "security.csrf.token_manager".'
        $container->prependExtensionConfig('framework', [
            'csrf_protection' => [
                'enabled' => true,
            ],
        ]);
    }
}
