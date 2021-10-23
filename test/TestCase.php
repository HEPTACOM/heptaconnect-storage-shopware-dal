<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected bool $setupKernel = true;

    protected ?ShopwareKernel $kernel = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->setupKernel) {
            $this->upKernel();
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->setupKernel) {
            $this->downKernel();
        }
    }

    protected function upKernel(): void
    {
        $this->kernel = new ShopwareKernel();
        $this->kernel->boot();

        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->beginTransaction();
    }

    protected function downKernel(): void
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->rollBack();
        $this->kernel->shutdown();
    }
}
