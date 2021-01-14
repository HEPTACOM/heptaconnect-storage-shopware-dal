<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset\Simple;

trait ProvideEntitiesTrait
{
    public function provideEntities(): iterable
    {
        $simple = new Simple();

        yield [clone $simple];
    }
}
