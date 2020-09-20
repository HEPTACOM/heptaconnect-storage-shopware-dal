<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MappingRepository extends MappingRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappings;

    public function __construct(StorageKeyGeneratorContract $storageKeyGenerator, EntityRepositoryInterface $mappings)
    {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappings = $mappings;
    }

    public function read(MappingKeyInterface $key): MappingInterface
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = Context::createDefaultContext();
        /** @var MappingCollection $mappings */
        $mappings = $this->mappings->search(new Criteria([$key->getUuid()]), $context)->getEntities();

        $mapping = $mappings->first();

        if (!$mapping instanceof MappingInterface) {
            throw new NotFoundException();
        }

        return $mapping;
    }

    public function listByNodes(
        MappingNodeKeyInterface $mappingNodeKey,
        PortalNodeKeyInterface $portalNodeKey
    ): iterable {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
        }

        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addFilter(
            new EqualsFilter('mappingNodeId', $mappingNodeKey->getUuid()),
            new EqualsFilter('portalNodeId', $portalNodeKey->getUuid())
        );
        $iterator = new RepositoryIterator($this->mappings, Context::createDefaultContext(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
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
        ]], Context::createDefaultContext());

        return $key;
    }

    public function updateExternalId(MappingKeyInterface $key, ?string $externalId): void
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = Context::createDefaultContext();
        $this->throwNotFoundWhenNoMatch($this->mappings, ['id' => $key->getUuid()], $context);
        $this->throwNotFoundWhenNoChange($this->mappings->update([[
            'id' => $key->getUuid(),
            'externalId' => $externalId,
        ]], $context));
    }

    public function delete(MappingKeyInterface $key): void
    {
        if (!$key instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = Context::createDefaultContext();
        $this->throwNotFoundWhenNoMatch($this->mappings, ['id' => $key->getUuid()], $context);
        $this->throwNotFoundWhenNoChange($this->mappings->delete([[
            'id' => $key->getUuid(),
        ]], $context));
    }
}
