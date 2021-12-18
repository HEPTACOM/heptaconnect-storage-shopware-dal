<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingExceptionStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Framework\Uuid\Uuid;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    private const IMPLEMENTATION_MAP = [
        PortalNodeKeyInterface::class => PortalNodeStorageKey::class,
        MappingNodeKeyInterface::class => MappingNodeStorageKey::class,
        CronjobKeyInterface::class => CronjobStorageKey::class,
        CronjobRunKeyInterface::class => CronjobRunStorageKey::class,
        RouteKeyInterface::class => RouteStorageKey::class,
        MappingKeyInterface::class => MappingStorageKey::class,
        MappingExceptionKeyInterface::class => MappingExceptionStorageKey::class,
        JobKeyInterface::class => JobStorageKey::class,
    ];

    private const ABBREVIATIONS = [
        'PortalNode' => PortalNodeStorageKey::class,
        'MappingNode' => MappingNodeStorageKey::class,
        'Cronjob' => CronjobStorageKey::class,
        'CronjobRun' => CronjobRunStorageKey::class,
        'Route' => RouteStorageKey::class,
        'Mapping' => MappingStorageKey::class,
        'MappingException' => MappingExceptionStorageKey::class,
        'Job' => JobStorageKey::class,
    ];

    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        return $this->createKey($keyClassName, null);
    }

    public function generateKeys(string $keyClassName, int $count): iterable
    {
        while ($count-- > 0) {
            yield $this->createKey($keyClassName, null);
        }
    }

    public function serialize(StorageKeyInterface $key): string
    {
        $class = \get_class($key);

        if (!$key instanceof AbstractStorageKey) {
            return parent::serialize($key);
        }

        if (($abbreviation = \array_search($class, self::ABBREVIATIONS, true)) === false) {
            throw new UnsupportedStorageKeyException($class);
        }

        return \sprintf('%s:%s', $abbreviation, $key->getUuid());
    }

    public function deserialize(string $keyData): StorageKeyInterface
    {
        $parts = \explode(':', $keyData, 2);

        if (\count($parts) !== 2) {
            return parent::deserialize($keyData);
        }

        [$abbreviation, $key] = $parts;

        if (\preg_match('/^[a-f0-9]{32}$/', $key) !== 1) {
            return parent::deserialize($keyData);
        }

        if (!\array_key_exists($abbreviation, self::ABBREVIATIONS)) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        $class = self::ABBREVIATIONS[$abbreviation];

        if (($interface = \array_search($class, self::IMPLEMENTATION_MAP, true)) === false) {
            throw new UnsupportedStorageKeyException(StorageKeyInterface::class);
        }

        return $this->createKey($interface, $key);
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
