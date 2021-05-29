<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Shopware\Core\Framework\Context;

class ContextFactory
{
    public function create(): Context
    {
        if (\method_exists(Context::class, 'disableCache')) {
            return Context::createDefaultContext()->disableCache(static fn (Context $context): Context => clone $context);
        }

        return Context::createDefaultContext();
    }
}
