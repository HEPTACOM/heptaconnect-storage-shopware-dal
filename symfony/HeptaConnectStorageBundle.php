<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDalSymfony;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class HeptaConnectStorageBundle extends Bundle
{
    #[\Override]
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../src/DependencyInjection/Resources/')))->load('services.php');
    }
}
