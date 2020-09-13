<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class PortalStorage extends PortalStorageContract
{
    private EntityRepositoryInterface $portalNodeStorages;

    public function __construct(EntityRepositoryInterface $portalNodeStorages)
    {
        $this->portalNodeStorages = $portalNodeStorages;
    }

    public function set(PortalNodeKeyInterface $portalNodeKey, string $key, string $value, string $type): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = Context::createDefaultContext();
        $storageId = Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();

        $this->portalNodeStorages->upsert([[
            'id' => $storageId,
            'portalNodeId' => $portalNodeKey->getUuid(),
            'key' => $key,
            'value' => $value,
            'type' => $type,
        ]], $context);
    }

    public function unset(PortalNodeKeyInterface $portalNodeKey, string $key): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = Context::createDefaultContext();
        $storageId = Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();
        $criteria = new Criteria([$storageId]);
        $criteria->setLimit(1);
        $searchResult = $this->portalNodeStorages->searchIds($criteria, $context);
        $storageId = $searchResult->firstId();

        if (\is_null($storageId)) {
            return;
        }

        $this->portalNodeStorages->delete([[
            'id' => $storageId,
        ]], $context);
    }

    public function getValue(PortalNodeKeyInterface $portalNodeKey, string $key): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = $this->innerGet($portalNodeKey->getUuid(), $key);

        if (!$result instanceof PortalNodeStorageEntity) {
            throw new NotFoundException();
        }

        return $result->getValue();
    }

    public function getType(PortalNodeKeyInterface $portalNodeKey, string $key): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = $this->innerGet($portalNodeKey->getUuid(), $key);

        if (!$result instanceof PortalNodeStorageEntity) {
            throw new NotFoundException();
        }

        return $result->getType();
    }

    public function has(PortalNodeKeyInterface $portalNodeKey, string $key): bool
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = Context::createDefaultContext();
        $storageId = Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();
        $criteria = new Criteria([$storageId]);
        $criteria->setLimit(1);
        $searchResult = $this->portalNodeStorages->searchIds($criteria, $context);

        return $searchResult->getTotal() > 0;
    }

    private function innerGet(string $portalNodeId, string $key): ?PortalNodeStorageEntity
    {
        $context = Context::createDefaultContext();
        $storageId = Uuid::uuid5($portalNodeId, $key)->getHex();
        $criteria = new Criteria([$storageId]);
        $criteria->setLimit(1);

        /** @var PortalNodeStorageCollection $entities */
        $entities = $this->portalNodeStorages->search($criteria, $context)->getEntities();

        return $entities->first();
    }
}
