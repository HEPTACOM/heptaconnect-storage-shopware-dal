<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Shopware\Core\Framework\Context;

class ContextFactory
{
    public function create(): Context
    {
        $result = Context::createDefaultContext();

        if (\method_exists($result, 'disableCache')) {
            return $result->disableCache(static fn (Context $context): Context => clone $context);
        }

        return $result;
    }
}
