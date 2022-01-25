# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add class `\Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor`
- Add state in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder` to make selects for update to trigger row locks
- Add constants for job states in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum`
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1639246133CreateStateHistoryForJobs` to add job state history
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1639270114InsertJobStates` to add job states
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1639860447UpdateExistingJobData` to migrate state date into job history
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobCreateActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobDeleteActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFailActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFail`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinish`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobGetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobListFinishedActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobScheduleActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobSchedule`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobStartActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobStart`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeOverviewActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeListActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate`
- Add exception code `1640048751` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate::create` when the key generator cannot generate a valid portal node key
- Add exception code `1640405544` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview::overview` when the criteria has an invalid sorting option
- Add exception code `1640405545` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview::overview` when query execution could not return a ResultStatement
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1640360050CreatePortalExtensionConfigurationTable` to add table for portal extension activity state
- Add base class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive` to simplify implementations of `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface` and `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionActivateActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionActivate`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionDeactivateActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionDeactivate`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionFind`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet`
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationSetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet`
- Add exception code `1642863637` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet::set` when the payload has an invalid portal node key
- Add exception code `1642863638` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet::set` when the payload value is not JSON serializable
- Add exception code `1642863639` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet::set` when writing to the database fails
- Add exception code `1642863471` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet::get` when query execution could not return a ResultStatement
- Add exception code `1642863472` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet::get` when the configuration value is not a valid JSON
- Add exception code `1642863473` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet::get` when the configuration value is not a JSON array or JSON object
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1642624782CreatePortalNodeConfigurationTable` to add table for portal node configuration and migrate from the previous storage
- Add exception code `1642937283` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1642624782CreatePortalNodeConfigurationTable::migrate` when the JSON value from the old storage cannot be parsed
- Add exception code `1642937284` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1642624782CreatePortalNodeConfigurationTable::migrate` when the JSON value from the old storage has an unexpected form
- Add exception code `1642937285` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1642624782CreatePortalNodeConfigurationTable::migrate` when the read JSON from the old storage cannot be transformed into JSON for the new storage
- Add exception code `1642940743` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::getIdsForTypes` when query execution could not return a ResultStatement
- Add exception code `1642940744` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::getIdsForTypes` when writing to the database fails
- Add exception code `1642951892` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::map` when writing to the database fails
- Add exception code `1642951893` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::map` when query execution could not return a ResultStatement
- Add exception code `1642951894` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::map` when query execution could not return a ResultStatement
- Implement `\Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade`

### Changed

- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Overview\RouteOverviewActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteOverviewActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\Overview\RouteCapabilityOverviewActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\RouteCapabilityOverviewActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface`
- Change interface of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet` from `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetActionInterface` to `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSetActionInterface`
- Rename `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap` and implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface`

### Deprecated

- Mark `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobPayloadCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobPayloadDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobPayloadEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobTypeCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobTypeDefinition` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobTypeEntity` as deprecated as DAL usage is discouraged
- Mark `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeCollection` as deprecated as DAL usage is discouraged

### Removed

- Remove class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Job` as base contract has been removed
- Remove class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobPayloadRepository` as base contract has been removed
- Remove class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository` as base contract has been removed
- Remove class `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey` as base contract has been removed and its support in `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepositoryContract::read` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet::get` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepositoryContract::listAll` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList::list` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepositoryContract::listByClass` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeOverview::overview` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepositoryContract::create` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate::create` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepositoryContract::create` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete::delete` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::getConfiguration` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationGet::get` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::setConfiguration` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration\PortalNodeConfigurationSet::set` that allows for optimizations for different use-cases
- Remove previously deprecated `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRepository` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRunRepository` as the feature of cronjobs in its current implementation is removed
- Remove previously deprecated `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunEntity` as the feature of cronjobs in its current implementation is removed
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1642885343RemoveCronjobAndCronjobRunTable` to remove the tables `heptaconnect_cronjob` and `heptaconnect_cronjob_run` as the feature of cronjobs in its current implementation is removed
- Replace dependencies in `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor` from `\Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface` to `\Doctrine\DBAL\Connection` to drop Shopware DAL usage

## [0.8.4] - 2022-01-22

### Added

- The `\Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister\MappingPersister` will now attempt to merge mapping-nodes when there are no conflicts. Now mappings can be integrated into an existing mapping-node during a reception.

## [0.8.3] - 2022-01-05

### Changed

- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1641403938AddChecksumIndexToJobPayloadTable` to add index to `checksum` to table `heptaconnect_job_payload` for improved listings and searches

## [0.8.2] - 2021-12-30

### Fixed

- Use target portal node key in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind` to query the target portal node instead of using the source portal node key

## [0.8.1] - 2021-11-22

### Fixed

- Replace exception code `1637467902` with `1637542091` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind::find` when query execution could not return a ResultStatement

## [0.8.0] - 2021-11-22

### Added

- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1632763825RenameDatasetEntityTypeTable` to rename database table `heptaconnect_dataset_entity_type` to `heptaconnect_entity_type`
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1629643769AddJobStartAndFinishFields` to add `started_at` and `finished_at` datetime columns into table `heptaconnect_job` for job processing tracking
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::start` to implement new `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract::start` for tracking the start of job processing
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::finish` to implement new `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract::finish` for tracking the stop of job processing
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::cleanup` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobPayloadRepository::cleanup` to implement new `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobRepositoryContract::cleanup` and `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract::cleanup` for cleaning up executed jobs and their payloads
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019143EntityTypeIndexHappenedAtColumns` to add descending indices to `created_at` and `updated_at` to table `heptaconnect_entity_type` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019144JobIndexHappenedAtColumns` to add descending indices to `created_at` and `updated_at` to table `heptaconnect_job` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019145JobIndexNewHappenedAtColumns` to add descending indices to `started_at` and `finished_at` to table `heptaconnect_job` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019146JobPayloadIndexHappenedAtColumns` to add descending indices to `created_at` and `updated_at` to table `heptaconnect_job_payload` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019147JobTypeIndexHappenedAtColumns` to add descending indices to `created_at` and `updated_at` to table `heptaconnect_job_type` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019148MappingIndexHappenedAtColumns` to add descending indices to `created_at`, `updated_at` and `deleted_at` to table `heptaconnect_mapping` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019149MappingErrorMessageIndexHappenedAtColumns` to add descending indices to `created_at` and `updated_at` to table `heptaconnect_mapping_error_message` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019150MappingNodeIndexHappenedAtColumns` to add descending indices to `created_at`, `updated_at` and `deleted_at` to table `heptaconnect_mapping_node` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019151PortalNodeIndexHappenedAtColumns` to add descending indices to `created_at`, `updated_at` and `deleted_at` to table `heptaconnect_portal_node` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019152PortalNodeStorageIndexHappenedAtColumns` to add descending indices to `created_at`, `updated_at` and `deleted_at` to table `heptaconnect_portal_node_storage` for improved listings and searches
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635019153RouteIndexHappenedAtColumns` to add descending indices to `created_at`, `updated_at` and `deleted_at` to table `heptaconnect_route` for improved listings and searches
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Listing\ReceptionRouteListActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Overview\RouteOverviewActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Get\RouteGetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate`
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator` to simplify DBAL paginated iteration
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635512814OnDeleteCascadeFromMappingNodeToMapping` to cascade delete from `heptaconnect_mapping_node` to `heptaconnect_mapping`
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635713039CreateRouteCapabilityTable` to create database table `heptaconnect_route_capability` to store route capability types
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635713040SeedReceptionRouteCapability` to add the reception capability type
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635713041CreateRouteToRouteCapabilityTable` to create database table `heptaconnect_route_has_capability` to connect routes to their capabilities
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1635713042SeedReceptionCapabilityToRoute` to add every capability type to every route
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\RouteCapability\Overview\RouteCapabilityOverviewActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview`
- Add custom `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder` based upon `\Doctrine\DBAL\Query\QueryBuilder` for parameterized pagination for easier SQL statement caching
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor` to read route capabilities efficiently for other internal operations
- Add exception code `1636505518` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview::overview` when the criteria has an invalid sorting option
- Add exception code `1636505519` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview::overview` when the criteria has an invalid sorting option
- Add exception code `1636573803` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when the payload refers to a source portal node with an invalid portal node
- Add exception code `1636573804` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when the payload refers to a target portal node with an invalid portal node
- Add exception code `1636573805` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when the payload refers to an unknown route capability
- Add exception code `1636573806` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when the payload refers to an unknown entity type
- Add exception code `1636573807` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when the key generator cannot generate a valid route key
- Add exception code `1636576240` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` when writing to the database fails
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1636817108CreateWebHttpHandlerPathTable` to create table `heptaconnect_web_http_handler_path` to hold indexed HTTP handler paths
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1636817109CreateWebHttpHandlerTable` to create table `heptaconnect_web_http_handler` to hold HTTP handlers based upon their portal nodes and paths
- Add migration `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1636817110CreateWebHttpHandlerConfigurationTable` to create table `heptaconnect_web_http_handler_configuration` to hold HTTP handler configurations 
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor` to read and insert HTTP handler entries efficiently for other internal operations
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor` to read and insert HTTP handler paths entries efficiently for other internal operations
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathIdResolver` to centralize path id prediction
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet`
- Add exception code `1636827821` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet::set` when the payload refers to an invalid portal node
- Add exception code `1636827822` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet::set` when the payload refers to an HTTP handler path that could not be looked up or created
- Add exception code `1636827823` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet::set` when the payload refers to an HTTP handler by path and portal node that could not be looked up or created
- Add exception code `1636827824` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSet::set` when writing to the database fails
- Add exception code `1637467897` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor::getIdsForPaths` when `\array_combine` returns false
- Add exception code `1636528918` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview::overview` when the criteria has an invalid sorting option
- Add exception code `1637467898` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor::getIdsForPaths` when query execution could not return a Statement
- Add exception code `1637467899` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor::getIdsForHandlers` when query execution could not return a Statement
- Add exception code `1637467900` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator::doIterate` when query execution could not return a ResultStatement
- Add exception code `1637467901` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor::getIdsForNames` when query execution could not return a ResultStatement
- Add exception code `1637467902` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind::find` when query execution could not return a Statement
- Add exception code `1637467903` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview::overview` when query execution could not return a ResultStatement
- Add exception code `1637467905` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview::overview` when query execution could not return a ResultStatement
- Add exception code `1637467906` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind::find` when query execution could not return a ResultStatement

### Changed

- Change namespace from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType` and rename folder appropriately
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeDefinition` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeCollection` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection`
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeEntity` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity`
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor`
- Change method name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity::getDatasetEntityClassName` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity::getEntityType`
- Change method name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity::getDatasetEntityClassName` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity::getEntityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType` from `$datasetEntityType` to `$entityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::getIdsForTypes` from `$datasetEntityClassNames` to `$entityTypes`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId` from `$datasetEntityClassName` to `$entityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds` from `$datasetEntityClassName` to `$entityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::create` from `$datasetEntityClassName` to `$entityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::createList` from `$datasetEntityClassName` to `$entityType`
- Change parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds` from `$datasetEntityClassName` to `$entityType`

### Fixed

- Change `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingExceptionRepository::create` so it includes a check for the success of `\json_encode`

### Deprecated

- Mark `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route\RouteDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route\RouteEntity` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route\RouteCollection` as deprecated as DAL usage is discouraged

### Removed

- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::listBySourceAndEntityType` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList::list`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview::overview` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind::find` that are optimized for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::read` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet::get` that is optimized for known use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::create` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteCreate::create` that is optimized for known use-cases
- Remove `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookDefinition` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookEntity` in favour of a storage independent solution
- Remove `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\WebhookRepository` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey` in favour of a storage independent solution
- Remove support for `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator`
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1636704625RemoveWebhookTable` to drop the `heptaconnect_webhook` table
- Remove support for `shopware/core: 6.2.*`
- Remove configuration merging from `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::setConfiguration` which is already done by the core package

## [0.7.0] - 2021-09-25

### Added

- Add support for composer dependency `ramsey/uuid: 4.*`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::clear`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getMultiple` and  `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::deleteMultiple` to allow PSR simple cache compatibility
- New service `\Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister` responsible for saving mappings after reception. Could improve usages of `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract`.

### Changed

- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`
- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`

### Fixed

- Change string comparison on database layer from whitespace-unaware, case-insensitive to binary for jobs, job payloads, mappings, portal nodes, portal node storage, data entity class names so lookups are one-to-one which therefore affects behaviour `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobPayloadRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::createList`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::updateExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository::listByClass`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::listBySourceAndEntityType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor::getIdsForTypes`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::set`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::unset`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getValue`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::has`
- Disable HTML stripping from string columns in DAL for jobs, mappings and portal node storage so storing data will allow `<>` symbols which therefore affects behaviour `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::updateExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::set`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::unset`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getValue`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::has`
- `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper` now respects soft-deletions of mappings and mapping nodes.
- `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector` now respects soft-deletions of mappings and mapping nodes.

## [0.6.0] - 2021-07-26

## [0.5.1] - 2021-07-13

## [0.5.0] - 2021-07-11

### Fixed

- Fix bug and improved performance on entity reflection in `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` when empty entity collection has been passed in

### Deprecated

- Deprecate cronjobs to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRunRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey`
- Deprecate webhooks to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\WebhookRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey`

## [0.4.0] - 2021-07-03

### Added

- Add support for preview portal node keys `\Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::getConfiguration`

### Changed

- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities` by restructuring database queries
- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` by restructuring database queries

## [0.3.1] - 2021-07-02
## [0.3.0] - 2021-07-02
