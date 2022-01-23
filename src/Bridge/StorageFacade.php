<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Support\AbstractSingletonStorageFacade;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFailActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobListFinishedActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobScheduleActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Mapping\MappingMapActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\RouteCapabilityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFail;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinish;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobSchedule;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobStart;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Mapping\MappingMap;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionDeactivate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathIdResolver;

class StorageFacade extends AbstractSingletonStorageFacade
{
    private Connection $connection;

    private ?StorageKeyGeneratorContract $storageKeyGenerator = null;

    private ?QueryIterator $queryIterator = null;

    private ?EntityTypeAccessor $entityTypeAccessor = null;

    private ?RouteCapabilityAccessor $routeCapabilityAccessor = null;

    private ?JobTypeAccessor $jobTypeAccessor = null;

    private ?WebHttpHandlerPathIdResolver $webHttpHandlerPathIdResolver = null;

    private ?WebHttpHandlerPathAccessor $webHttpHandlerPathAccessor = null;

    private ?WebHttpHandlerAccessor $webHttpHandlerAccessor = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    protected function createJobCreateAction(): JobCreateActionInterface
    {
        return new JobCreate($this->connection, $this->getStorageKeyGenerator(), $this->getJobTypeAccessor(), $this->getEntityTypeAccessor());
    }

    protected function createJobDeleteAction(): JobDeleteActionInterface
    {
        return new JobDelete($this->connection);
    }

    protected function createJobFailAction(): JobFailActionInterface
    {
        return new JobFail($this->connection);
    }

    protected function createJobFinishAction(): JobFinishActionInterface
    {
        return new JobFinish($this->connection);
    }

    protected function createJobGetAction(): JobGetActionInterface
    {
        return new JobGet($this->connection, $this->getQueryIterator());
    }

    protected function createJobListFinishedAction(): JobListFinishedActionInterface
    {
        return new JobFinishedList($this->connection, $this->getQueryIterator());
    }

    protected function createJobScheduleAction(): JobScheduleActionInterface
    {
        return new JobSchedule($this->connection);
    }

    protected function createJobStartAction(): JobStartActionInterface
    {
        return new JobStart($this->connection);
    }

    protected function createMappingMapAction(): MappingMapActionInterface
    {
        return new MappingMap($this->getStorageKeyGenerator(), $this->getEntityTypeAccessor(), $this->connection);
    }

    protected function createPortalExtensionActivateAction(): PortalExtensionActivateActionInterface
    {
        return new PortalExtensionActivate($this->connection);
    }

    protected function createPortalExtensionDeactivateAction(): PortalExtensionDeactivateActionInterface
    {
        return new PortalExtensionDeactivate($this->connection);
    }

    protected function createPortalExtensionFindAction(): PortalExtensionFindActionInterface
    {
        return new PortalExtensionFind($this->connection);
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

    protected function createPortalNodeConfigurationGetAction(): PortalNodeConfigurationGetActionInterface
    {
        return new PortalNodeConfigurationGet($this->connection);
    }

    protected function createPortalNodeConfigurationSetAction(): PortalNodeConfigurationSetActionInterface
    {
        return new PortalNodeConfigurationSet($this->connection);
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

    protected function createRouteOverviewAction(): RouteOverviewActionInterface
    {
        return new RouteOverview($this->connection);
    }

    protected function createRouteCapabilityOverviewAction(): RouteCapabilityOverviewActionInterface
    {
        return new RouteCapabilityOverview($this->connection);
    }

    protected function createWebHttpHandlerConfigurationFindAction(): WebHttpHandlerConfigurationFindActionInterface
    {
        return new WebHttpHandlerConfigurationFind($this->connection, $this->getWebHttpHandlerPathIdResolver());
    }

    protected function createWebHttpHandlerConfigurationSetAction(): WebHttpHandlerConfigurationSetActionInterface
    {
        return new WebHttpHandlerConfigurationSet(
            $this->connection,
            $this->getWebHttpHandlerPathAccessor(),
            $this->getWebHttpHandlerAccessor()
        );
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
        return $this->entityTypeAccessor ??= new EntityTypeAccessor($this->connection);
    }

    private function getRouteCapabilityAccessor(): RouteCapabilityAccessor
    {
        return $this->routeCapabilityAccessor ??= new RouteCapabilityAccessor($this->connection);
    }

    private function getJobTypeAccessor(): JobTypeAccessor
    {
        return $this->jobTypeAccessor ??= new JobTypeAccessor($this->connection);
    }

    private function getWebHttpHandlerPathIdResolver(): WebHttpHandlerPathIdResolver
    {
        return $this->webHttpHandlerPathIdResolver ??= new WebHttpHandlerPathIdResolver();
    }

    private function getWebHttpHandlerPathAccessor(): WebHttpHandlerPathAccessor
    {
        return $this->webHttpHandlerPathAccessor ??= new WebHttpHandlerPathAccessor(
            $this->connection,
            $this->getWebHttpHandlerPathIdResolver()
        );
    }

    private function getWebHttpHandlerAccessor(): WebHttpHandlerAccessor
    {
        return $this->webHttpHandlerAccessor ??= new WebHttpHandlerAccessor(
            $this->connection,
            $this->getWebHttpHandlerPathIdResolver()
        );
    }
}
