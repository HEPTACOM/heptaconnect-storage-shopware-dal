<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\AliasAwarePortalNodeStorageKey;

final class PortalNodeStorageKey extends AbstractStorageKey implements PortalNodeKeyInterface
{
    public function withAlias(): PortalNodeKeyInterface
    {
        return new AliasAwarePortalNodeStorageKey($this);
    }

    public function withoutAlias(): PortalNodeKeyInterface
    {
        return $this;
    }
}
