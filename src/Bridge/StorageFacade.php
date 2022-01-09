<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Support\AbstractSingletonStorageFacade;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview;
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

    protected function createPortalNodeCreateAction(): PortalNodeCreateActionInterface
    {
        return new PortalNodeCreate($this->connection, $this->getStorageKeyGenerator());
    }

    protected function createPortalNodeDeleteAction(): PortalNodeDeleteActionInterface
    {
        return new PortalNodeDelete($this->connection);
    }

    protected function createPortalNodeGetAction(): PortalNodeGetActionInterface
    {
        return new PortalNodeGet($this->connection, $this->getQueryIterator());
    }

    protected function createPortalNodeListAction(): PortalNodeListActionInterface
    {
        return new PortalNodeList($this->connection, $this->getQueryIterator());
    }

    protected function createPortalNodeOverviewAction(): PortalNodeOverviewActionInterface
    {
        return new PortalNodeOverview($this->connection);
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
