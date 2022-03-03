<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

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

        $storageId = (string) Uuid::uuid5($portalNodeKey->getUuid(), $key)->getHex();

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

        while (($searchResult = $iterator->fetch()) !== null && ($entities = $searchResult->getEntities())->count() > 0) {
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
}
