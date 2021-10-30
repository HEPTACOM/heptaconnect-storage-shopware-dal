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
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class RouteRepository extends RouteRepositoryContract
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityRepositoryInterface $routes;

    private ContextFactory $contextFactory;

    private EntityTypeAccessor $entityTypeAccessor;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityRepositoryInterface $routes,
        ContextFactory $contextFactory,
        EntityTypeAccessor $entityTypeAccessor
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->routes = $routes;
        $this->contextFactory = $contextFactory;
        $this->entityTypeAccessor = $entityTypeAccessor;
    }

    public function read(RouteKeyInterface $key): RouteInterface
    {
        if (!$key instanceof RouteStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $criteria = new Criteria([$key->getUuid()]);
        $criteria->addFilter(new EqualsFilter('deletedAt', null));
        $criteria->addAssociation('type');
        $route = $this->routes->search($criteria, $this->contextFactory->create())->first();

        if (!$route instanceof RouteInterface) {
            throw new NotFoundException();
        }

        return $route;
    }

    public function create(
        PortalNodeKeyInterface $sourceKey,
        PortalNodeKeyInterface $targetKey,
        string $entityType
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
        $typeId = $this->entityTypeAccessor->getIdsForTypes([$entityType], $context)[$entityType];

        $this->routes->create([[
            'id' => $key->getUuid(),
            'typeId' => $typeId,
            'sourceId' => $sourceKey->getUuid(),
            'targetId' => $targetKey->getUuid(),
        ]], $context);

        return $key;
    }
}
