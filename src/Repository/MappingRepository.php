<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\MappingKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\MappingCollection as StorageMappingCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MappingRepository extends MappingRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappings;

    private ContextFactory $contextFactory;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappings,
        ContextFactory $contextFactory
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappings = $mappings;
        $this->contextFactory = $contextFactory;
    }

    public function read(MappingKeyInterface $key): MappingInterface
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();

        $criteria = new Criteria([$key->getUuid()]);
        $criteria->addFilter(new EqualsFilter('deletedAt', null));

        /** @var MappingCollection $mappings */
        $mappings = $this->mappings->search($criteria, $context)->getEntities();

        $mapping = $mappings->first();

        if (!$mapping instanceof MappingInterface) {
            throw new NotFoundException();
        }

        return $mapping;
    }

    public function listByMappingNode(MappingNodeKeyInterface $mappingNodeKey): iterable
    {
        if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
        }

        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addFilter(
            new EqualsFilter('mappingNodeId', $mappingNodeKey->getUuid()),
            new EqualsFilter('deletedAt', null)
        );

        $iterator = new RepositoryIterator($this->mappings, $this->contextFactory->create(), $criteria);

        while (($ids = $iterator->fetchIds()) !== null) {
            foreach ($ids as $id) {
                yield new MappingStorageKey($id);
            }
        }
    }

    public function listByPortalNodeAndType(PortalNodeKeyInterface $portalNodeKey, string $entityType): iterable
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addFilter(
            new EqualsFilter('mappingNode.type.type', $entityType),
            new EqualsFilter('portalNodeId', $portalNodeKey->getUuid()),
            new EqualsFilter('deletedAt', null)
        );

        $iterator = new RepositoryIterator($this->mappings, $this->contextFactory->create(), $criteria);

        while (($ids = $iterator->fetchIds()) !== null) {
            foreach ($ids as $id) {
                yield new MappingStorageKey($id);
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        MappingNodeKeyInterface $mappingNodeKey,
        ?string $externalId
    ): MappingKeyInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
        }

        $key = $this->storageKeyGenerator->generateKey(MappingKeyInterface::class);

        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->mappings->create([[
            'id' => $key->getUuid(),
            'externalId' => $externalId,
            'mappingNodeId' => $mappingNodeKey->getUuid(),
            'portalNodeId' => $portalNodeKey->getUuid(),
        ]], $this->contextFactory->create());

        return $key;
    }

    public function createList(StorageMappingCollection $mappings): MappingKeyCollection
    {
        $result = new MappingKeyCollection();
        $payload = [];

        /** @var MappingInterface $mapping */
        foreach ($mappings as $mapping) {
            $portalNodeKey = $mapping->getPortalNodeKey();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $mappingNodeKey = $mapping->getMappingNodeKey();

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
            }

            $key = $this->storageKeyGenerator->generateKey(MappingKeyInterface::class);

            if (!$key instanceof MappingStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $payload[] = [
                'id' => $key->getUuid(),
                'externalId' => $mapping->getExternalId(),
                'mappingNodeId' => $mappingNodeKey->getUuid(),
                'portalNodeId' => $portalNodeKey->getUuid(),
            ];
            $result->push([$key]);
        }

        if ($payload !== []) {
            $this->mappings->create($payload, $this->contextFactory->create());
        }

        return $result;
    }

    public function delete(MappingKeyInterface $key): void
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();
        $this->throwNotFoundWhenNoMatch($this->mappings, $key->getUuid(), $context);
        $this->throwNotFoundWhenNoChange($this->mappings->update([[
            'id' => $key->getUuid(),
            'deletedAt' => \date_create(),
        ]], $context));
    }
}
