<?php

declare(strict_types=1);

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Dotenv\Dotenv;

// custom test suite base class
include_once __DIR__ . '/../test-suite/TestCase.php';

/** @var Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__ . '/../vendor/autoload.php';
KernelLifecycleManager::prepare($loader);

(new Dotenv(true))->load(__DIR__ . '/../.env.test');

$connection = ShopwareKernel::getConnection();

function sqlExec(Connection $connection, string $sql): void
{
    // doctrine/dbal 2 support
    if (\method_exists($connection, 'executeStatement')) {
        $connection->executeStatement($sql);
    } else {
        $connection->exec($sql);
    }
}

sqlExec($connection, 'SET FOREIGN_KEY_CHECKS = 0');

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

sqlExec($connection, 'SET FOREIGN_KEY_CHECKS = 1');
sqlExec($connection, \file_get_contents(__DIR__ . '/../vendor/shopware/core/schema.sql'));

$kernel = new ShopwareKernel();
$kernel->boot();
$kernel->registerBundles();
$application = new Application($kernel);
$command = $application->find('database:migrate');
$result = $command->run(new StringInput('--all'), new NullOutput());
$result = $command->run(new StringInput('database:migrate --all HeptaConnectStorage'), new NullOutput());
