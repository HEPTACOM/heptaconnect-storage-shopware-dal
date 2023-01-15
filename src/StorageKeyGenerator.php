<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\IdentityErrorKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\AliasAwarePortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\IdentityRedirectKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityRedirectStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityErrorStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    private const IMPLEMENTATION_MAP = [
        PortalNodeKeyInterface::class => PortalNodeStorageKey::class,
        MappingNodeKeyInterface::class => MappingNodeStorageKey::class,
        RouteKeyInterface::class => RouteStorageKey::class,
        IdentityRedirectKeyInterface::class => IdentityRedirectStorageKey::class,
        IdentityErrorKeyInterface::class => IdentityErrorStorageKey::class,
        JobKeyInterface::class => JobStorageKey::class,
        FileReferenceRequestKeyInterface::class => FileReferenceRequestStorageKey::class,
    ];

    private const ABBREVIATIONS = [
        'PortalNode' => PortalNodeStorageKey::class,
        'MappingNode' => MappingNodeStorageKey::class,
        'Route' => RouteStorageKey::class,
        'IdentityRedirect' => IdentityRedirectStorageKey::class,
        'IdentityError' => IdentityErrorStorageKey::class,
        'MappingException' => IdentityErrorStorageKey::class,
        'Job' => JobStorageKey::class,
        'FileReferenceRequest' => FileReferenceRequestStorageKey::class,
    ];

    private PortalNodeAliasAccessor $portalNodeAliasAccessor;

    public function __construct(PortalNodeAliasAccessor $portalNodeAliasAccessor)
    {
        $this->portalNodeAliasAccessor = $portalNodeAliasAccessor;
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

        if ($key instanceof AliasAwarePortalNodeStorageKey) {
            $key = $key->withoutAlias();
            $class = \get_class($key);

            if (!$key instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException($class);
            }

            $alias = $this->portalNodeAliasAccessor->getAliasesByIds([$key->getUuid()])[$key->getUuid()] ?? null;

            if (\is_string($alias)) {
                return $alias;
            }
        }

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
        $portalNodeKeyData = $this->portalNodeAliasAccessor->getIdsByAliases([$keyData])[$keyData] ?? null;

        if (\is_string($portalNodeKeyData)) {
            return new PortalNodeStorageKey($portalNodeKeyData);
        }

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
        $uuid ??= Id::randomHex();

        if (!\array_key_exists($interface, self::IMPLEMENTATION_MAP)) {
            throw new UnsupportedStorageKeyException($interface);
        }

        $class = self::IMPLEMENTATION_MAP[$interface];

        return new $class($uuid);
    }
}
