<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class MappingNodeRepository extends MappingNodeRepositoryContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $mappingNodes;

    private EntityRepositoryInterface $datasetEntityTypes;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $mappingNodes,
        EntityRepositoryInterface $datasetEntityTypes
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->mappingNodes = $mappingNodes;
        $this->datasetEntityTypes = $datasetEntityTypes;
    }

    public function read(MappingNodeKeyInterface $key): MappingNodeStructInterface
    {
        if (!$key instanceof MappingNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $criteria = new Criteria([$key->getUuid()]);
        $item = $this->mappingNodes->search($criteria, Context::createDefaultContext())->first();

        if (!$item instanceof MappingNodeStructInterface) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function listByTypeAndPortalNodeAndExternalId(
        string $datasetEntityClassName, PortalNodeKeyInterface $portalNodeKey, string $externalId
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

        $iterator = new RepositoryIterator($this->mappingNodes, Context::createDefaultContext(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new MappingNodeStorageKey($id);
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

        $context = Context::createDefaultContext();
        $typeIds = $this->getIdsForDatasetEntityType([$datasetEntityClassName], $context);

        $this->mappingNodes->create([[
            'id' => $mappingId->getUuid(),
            'originPortalNodeId' => $portalNodeKey->getUuid(),
            'typeId' => $typeIds[$datasetEntityClassName],
        ]], $context);

        return $mappingId;
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
