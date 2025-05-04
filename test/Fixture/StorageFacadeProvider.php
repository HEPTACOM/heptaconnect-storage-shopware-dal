<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class StorageFacadeProvider
{
    public static function createContainerStorageFacade(Connection $connection): StorageFacade
    {
        $container = new ContainerBuilder();

        $container->set(Connection::class, $connection);
        $container->set(LoggerInterface::class, new NullLogger());
        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../symfony/DependencyInjection/Resources/')))->load('services.php');

        $container->compile();

        return new StorageFacade($container);
    }
}
