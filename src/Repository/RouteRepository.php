<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\RouteInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\RouteRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteRepository extends RouteRepositoryContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $routes;

    private EntityRepositoryInterface $datasetEntityTypes;

    private ContextFactory $contextFactory;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $routes,
        EntityRepositoryInterface $datasetEntityTypes,
        ContextFactory $contextFactory
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->routes = $routes;
        $this->datasetEntityTypes = $datasetEntityTypes;
        $this->contextFactory = $contextFactory;
    }

    public function read(RouteKeyInterface $key): RouteInterface
    {
        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $criteria = new Criteria([$key->getUuid()]);
        $criteria->addAssociation('type');
        $route = $this->routes->search($criteria, $this->contextFactory->create())->first();

        if (!$route instanceof RouteInterface) {
            throw new NotFoundException();
        }

        return $route;
    }

    public function listBySourceAndEntityType(PortalNodeKeyInterface $sourceKey, string $entityClassName): iterable
    {
        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $criteria = (new Criteria())
            ->setLimit(100)
            ->addFilter(
                new EqualsFilter('type.type', $entityClassName),
                new EqualsFilter('sourceId', $sourceKey->getUuid())
            );
        $iterator = new RepositoryIterator($this->routes, $this->contextFactory->create(), $criteria);

        while (!\is_null($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new RouteStorageKey($id);
            }
        }
    }

    public function create(
        PortalNodeKeyInterface $sourceKey,
        PortalNodeKeyInterface $targetKey,
        string $entityClassName
    ): RouteKeyInterface {
        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        if (!$targetKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        $key = $this->storageKeyGenerator->generateKey(RouteKeyInterface::class);

        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();
        $typeId = $this->getIdsForDatasetEntityType([$entityClassName], $context)[$entityClassName];

        $this->routes->create([[
            'id' => $key->getUuid(),
            'typeId' => $typeId,
            'sourceId' => $sourceKey->getUuid(),
            'targetId' => $targetKey->getUuid(),
        ]], $context);

        return $key;
    }

    /**
     * @psalm-param array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>> $types
     * @psalm-return array<class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>, string>
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
