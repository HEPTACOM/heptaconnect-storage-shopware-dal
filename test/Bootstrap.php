<?php
declare(strict_types=1);

use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Component\Dotenv\Dotenv;

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';
KernelLifecycleManager::prepare($loader);

(new Dotenv(true))->load(__DIR__.'/../.env.test');

$connection = ShopwareKernel::getConnection();
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

do {
    $tables = $connection->getSchemaManager()->listTableNames();

    foreach ($tables as $table) {
        $table = "`$table`";

        foreach ($connection->getSchemaManager()->listTableIndexes($table) as $index) {
            $connection->getSchemaManager()->dropIndex($index, $table);
        }

        try {
            $connection->getSchemaManager()->dropTable($table);
        } catch (\Throwable $throwable) {
        }
    }
} while ($tables !== []);
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

$connection->executeStatement(\file_get_contents(__DIR__.'/../vendor/shopware/core/schema.sql'));
