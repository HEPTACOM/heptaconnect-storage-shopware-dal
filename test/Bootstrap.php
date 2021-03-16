<?php
declare(strict_types=1);

use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';
KernelLifecycleManager::prepare($loader);

(new Dotenv(true))->load(__DIR__.'/../.env.test');

$connection = \Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel::getConnection();

if (!$connection->getSchemaManager()->tablesExist('migration')) {
    $connection->exec(\file_get_contents(__DIR__.'/../vendor/shopware/core/schema.sql'));
}
