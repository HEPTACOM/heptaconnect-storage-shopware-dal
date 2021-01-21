<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\MappingNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class MappingNodeRepository extends MappingNodeRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappingNodes;

    private EntityRepositoryInterface $mappings;

    private EntityRepositoryInterface $datasetEntityTypes;

    private ContextFactory $contextFactory;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappingNodes,
        EntityRepositoryInterface $mappings,
        EntityRepositoryInterface $datasetEntityTypes,
        ContextFactory $contextFactory
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodes = $mappingNodes;
        $this->mappings = $mappings;
        $this->datasetEntityTypes = $datasetEntityTypes;
        $this->contextFactory = $contextFactory;
    }

    public function read(MappingNodeKeyInterface $key): MappingNodeStructInterface
    {
        if (!$key instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $criteria = new Criteria([$key->getUuid()]);
        $item = $this->mappingNodes->search($criteria, $this->contextFactory->create())->first();

        if (!$item instanceof MappingNodeStructInterface) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function listByTypeAndPortalNodeAndExternalId(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        string $externalId
    ): iterable {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(
                new EqualsFilter('deletedAt', null),
                new EqualsFilter('type.type', $datasetEntityClassName),
                new EqualsFilter('mappings.deletedAt', null),
                new EqualsFilter('mappings.externalId', $externalId),
                new EqualsFilter('mappings.portalNode.deletedAt', null),
                new EqualsFilter('mappings.portalNode.id', $portalNodeKey->getUuid()),
            );

        // TODO: Do not use iterator. We only expect one result.
        $iterator = new RepositoryIterator($this->mappingNodes, $this->contextFactory->create(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new MappingNodeStorageKey($id);
            }
        }
    }

    public function listByTypeAndPortalNodeAndExternalIds(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        array $externalIds
    ): iterable {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        if ($externalIds === []) {
            yield from [];
        }

        $criteria = (new Criteria())
            ->setLimit(50)
            ->addFilter(
                new EqualsFilter('mappingNode.deletedAt', null),
                new EqualsFilter('mappingNode.type.type', $datasetEntityClassName),
                new EqualsFilter('deletedAt', null),
                new EqualsAnyFilter('externalId', $externalIds),
                new EqualsFilter('portalNode.deletedAt', null),
                new EqualsFilter('portalNode.id', $portalNodeKey->getUuid()),
            );

        $iterator = new RepositoryIterator($this->mappings, $this->contextFactory->create(), $criteria);

        while (($result = $iterator->fetch()) instanceof EntitySearchResult) {
            /** @var MappingEntity $entity */
            foreach ($result->getIterator() as $entity) {
                yield $entity->getExternalId() => new MappingNodeStorageKey($entity->getMappingNodeId());
            }
        }
    }

    public function create(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey
    ): MappingNodeKeyInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $mappingId = $this->storageKeyGenerator->generateKey(MappingNodeKeyInterface::class);

        if (!$mappingId instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingId));
        }

        $context = $this->contextFactory->create();
        $typeIds = $this->getIdsForDatasetEntityType([$datasetEntityClassName], $context);

        $this->mappingNodes->create([[
            'id' => $mappingId->getUuid(),
            'originPortalNodeId' => $portalNodeKey->getUuid(),
            'typeId' => $typeIds[$datasetEntityClassName],
        ]], $context);

        return $mappingId;
    }

    public function createList(
        string $datasetEntityClassName,
        PortalNodeKeyInterface $portalNodeKey,
        int $count
    ): MappingNodeKeyCollection {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $result = new MappingNodeKeyCollection($this->storageKeyGenerator->generateKeys(MappingNodeKeyInterface::class, $count));

        if ($result->count() !== $count) {
            throw new UnsupportedStorageKeyException(MappingNodeKeyInterface::class);
        }

        $context = $this->contextFactory->create();
        $typeIds = $this->getIdsForDatasetEntityType([$datasetEntityClassName], $context);
        $payload = [];

        /** @var MappingNodeStorageKey $key */
        foreach ($result as $key) {
            if (!$key instanceof MappingNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $payload[] = [
                'id' => $key->getUuid(),
                'originPortalNodeId' => $portalNodeKey->getUuid(),
                'typeId' => $typeIds[$datasetEntityClassName],
            ];
        }

        if ($payload !== []) {
            $this->mappingNodes->create($payload, $context);
        }

        return $result;
    }

    public function delete(MappingNodeKeyInterface $key): void
    {
        if (!$key instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();
        $this->throwNotFoundWhenNoMatch($this->mappingNodes, $key->getUuid(), $context);
        $this->throwNotFoundWhenNoChange($this->mappingNodes->delete([[
            'id' => $key->getUuid(),
        ]], $context));
    }

    /**
     * @psalm-param array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>> $types
     * @psalm-return array<class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>, string>
     */
    private function getIdsForDatasetEntityType(array $types, Context $context): array
    {
        $datasetEntityCriteria = new Criteria();
        $datasetEntityCriteria->addFilter(new EqualsAnyFilter('type', $types));
        /** @var DatasetEntityTypeCollection $datasetTypeEntities */
        $datasetTypeEntities = $this->datasetEntityTypes->search($datasetEntityCriteria, $context)->getEntities();
        $typeIds = $datasetTypeEntities->groupByType();
        $datasetTypeInsert = [];

        foreach ($types as $className) {
            if (!\array_key_exists($className, $typeIds)) {
                $id = Uuid::randomHex();
                $datasetTypeInsert[] = [
                    'id' => $id,
                    'type' => $className,
                ];
                $typeIds[$className] = $id;
            }
        }

        if (\count($datasetTypeInsert) > 0) {
            $this->datasetEntityTypes->create($datasetTypeInsert, $context);
        }

        return $typeIds;
    }
}
