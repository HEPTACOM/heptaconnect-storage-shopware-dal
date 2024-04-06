<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\AliasAwarePortalNodeStorageKey;

abstract class AbstractStorageKey implements StorageKeyInterface
{
    public function __construct(
        private string $uuid
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): AbstractStorageKey
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function equals(StorageKeyInterface $other): bool
    {
        if ($other instanceof AliasAwarePortalNodeStorageKey) {
            $other = $other->withoutAlias();
        }

        if (!\is_a($other, static::class, false)) {
            return false;
        }

        /* @var $other AbstractStorageKey */
        return $other->getUuid() === $this->getUuid();
    }

    /**
     * @return mixed|string
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->uuid;
    }
}
