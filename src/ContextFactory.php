<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Shopware\Core\Framework\Context;

class ContextFactory
{
    public function create(): Context
    {
        return Context::createDefaultContext()->disableCache(static fn (Context $context): Context => clone $context);
    }
}
