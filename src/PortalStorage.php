<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;

class PortalStorage extends PortalStorageContract
{
    private EntityRepositoryInterface $portalNodeStorages;

    private ContextFactory $contextFactory;

    public function __construct(EntityRepositoryInterface $portalNodeStorages, ContextFactory $contextFactory)
    {
        $this->portalNodeStorages = $portalNodeStorages;
        $this->contextFactory = $contextFactory;
    }

    public function set(
        PortalNodeKeyInterface $portalNodeKey,
        string $key,
        string $value,
        string $type,
        ?\DateInterval $ttl = null
    ): void {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $storageId = Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();

        $upsert = [
            'id' => $storageId,
            'portalNodeId' => $portalNodeKey->getUuid(),
            'key' => $key,
            'value' => $value,
            'type' => $type,
        ];

        if ($ttl instanceof \DateInterval) {
            $upsert['expiredAt'] = (new \DateTimeImmutable())->add($ttl);
        } else {
            $upsert['expiredAt'] = null;
        }

        $this->portalNodeStorages->upsert([$upsert], $this->contextFactory->create());
    }

    public function unset(PortalNodeKeyInterface $portalNodeKey, string $key): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = $this->contextFactory->create();
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

    public function list(PortalNodeKeyInterface $portalNodeKey): iterable
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = $this->contextFactory->create();
        $criteria = (new Criteria())
            ->setLimit(50)
            ->addFilter(new EqualsFilter('portalNodeId', $portalNodeKey->getUuid()))
        ;

        $iterator = new RepositoryIterator($this->portalNodeStorages, $context, $criteria);

        while (($searchResult = $iterator->fetch()) !== null && !empty($entities = $searchResult->getEntities())) {
            foreach ($entities as $entity) {
                if ($entity instanceof PortalNodeStorageEntity) {
                    yield $entity->getKey() => [
                        'type' => $entity->getType(),
                        'value' => $entity->getValue(),
                    ];
                }
            }
        }
    }

    public function has(PortalNodeKeyInterface $portalNodeKey, string $key): bool
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $storageId = Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();
        $criteria = new Criteria([$storageId]);
        $criteria->setLimit(1);
        $searchResult = $this->portalNodeStorages->searchIds($criteria, $this->contextFactory->create());

        return $searchResult->getTotal() > 0;
    }

    private function innerGet(string $portalNodeId, string $key): ?PortalNodeStorageEntity
    {
        $storageId = Uuid::uuid5($portalNodeId, $key)->getHex();
        $criteria = new Criteria([$storageId]);
        $criteria->setLimit(1);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsFilter('expiredAt', null),
            new RangeFilter('expiredAt', [
                RangeFilter::GT => (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]),
        ]));

        /** @var PortalNodeStorageCollection $entities */
        $entities = $this->portalNodeStorages->search($criteria, $this->contextFactory->create())->getEntities();

        return $entities->first();
    }
}
