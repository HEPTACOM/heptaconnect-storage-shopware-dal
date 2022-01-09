<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Support\AbstractSingletonStorageFacade;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class StorageFacade extends AbstractSingletonStorageFacade
{
    private Connection $connection;

    private EntityRepositoryInterface $entityTypeRepository;

    private ?StorageKeyGeneratorContract $storageKeyGenerator = null;

    private ?QueryIterator $queryIterator = null;

    private ?EntityTypeAccessor $entityTypeAccessor = null;

    private ?RouteCapabilityAccessor $routeCapabilityAccessor = null;

    public function __construct(Connection $connection, EntityRepositoryInterface $entityTypeRepository)
    {
        $this->connection = $connection;
        $this->entityTypeRepository = $entityTypeRepository;
    }

    protected function createRouteCreateAction(): RouteCreateActionInterface
    {
        return new RouteCreate(
            $this->connection,
            $this->getStorageKeyGenerator(),
            $this->getEntityTypeAccessor(),
            $this->getRouteCapabilityAccessor()
        );
    }

    protected function createRouteFindAction(): RouteFindActionInterface
    {
        return new RouteFind($this->connection);
    }

    protected function createRouteGetAction(): RouteGetActionInterface
    {
        return new RouteGet($this->connection, $this->getQueryIterator());
    }

    protected function createReceptionRouteListAction(): ReceptionRouteListActionInterface
    {
        return new ReceptionRouteList($this->connection, $this->getQueryIterator());
    }

    private function getStorageKeyGenerator(): StorageKeyGeneratorContract
    {
        return $this->storageKeyGenerator ??= new StorageKeyGenerator();
    }

    private function getQueryIterator(): QueryIterator
    {
        return $this->queryIterator ??= new QueryIterator();
    }

    private function getEntityTypeAccessor(): EntityTypeAccessor
    {
        return $this->entityTypeAccessor ??= new EntityTypeAccessor($this->entityTypeRepository);
    }

    private function getRouteCapabilityAccessor(): RouteCapabilityAccessor
    {
        return $this->routeCapabilityAccessor ??= new RouteCapabilityAccessor($this->connection);
    }
}
