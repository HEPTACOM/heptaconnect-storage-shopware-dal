<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\PortalExtension;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;

class PortalExtension extends PortalExtensionContract
{
    protected function supports(): string
    {
        return Portal::class;
    }
}
