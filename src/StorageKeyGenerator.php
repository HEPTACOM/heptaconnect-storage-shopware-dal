<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey;
use Shopware\Core\Framework\Uuid\Uuid;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    private const IMPLEMENTATION_MAP = [
        PortalNodeKeyInterface::class => PortalNodeStorageKey::class,
        MappingNodeKeyInterface::class => MappingNodeStorageKey::class,
        WebhookKeyInterface::class => WebhookStorageKey::class,
        CronjobKeyInterface::class => CronjobStorageKey::class,
    ];

    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        return $this->createKey($keyClassName, null);
    }

    public function serialize(StorageKeyInterface $key): string
    {
        $class = \get_class($key);

        if (!$key instanceof AbstractStorageKey) {
            throw new UnsupportedStorageKeyException($class);
        }

        if (($interface = \array_search($class, self::IMPLEMENTATION_MAP, true)) === false) {
            throw new UnsupportedStorageKeyException($class);
        }

        return \sprintf('%s:%s', $interface, $key->getUuid());
    }

    public function deserialize(string $keyData): StorageKeyInterface
    {
        [$interface, $key] = \explode(':', $keyData, 2);

        if (\preg_match('/^[a-f0-9]{32}$/', $key) !== 1) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        if (!\array_key_exists($interface, self::IMPLEMENTATION_MAP)) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        return $this->createKey(self::IMPLEMENTATION_MAP[$interface], (string) $key);
    }

    private function createKey(string $interface, ?string $uuid): StorageKeyInterface
    {
        $uuid ??= Uuid::randomHex();

        if (!\array_key_exists($interface, self::IMPLEMENTATION_MAP)) {
            throw new UnsupportedStorageKeyException($interface);
        }

        $class = self::IMPLEMENTATION_MAP[$interface];

        return new $class($uuid);
    }
}
