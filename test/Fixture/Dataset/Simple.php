<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Dataset;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;

class Simple extends DatasetEntityContract
{
    protected string $info = '';

    public function getInfo(): string
    {
        return $this->info;
    }

    public function setInfo(string $info): void
    {
        $this->info = $info;
    }
}
