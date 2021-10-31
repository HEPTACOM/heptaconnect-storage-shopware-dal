# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\ReceptionRouteListActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\ReceptionRouteList`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\RouteOverviewActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteOverview`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\RouteFindByTargetsAndTypeActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteFindByTargetsAndType`
- Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\RouteGetActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteGet`
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator` to simplify DBAL paginated iteration

### Changed

- Change namespace from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType` and rename folder appropriately
- Change class from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeDefinition` name to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition` in global refactoring effort
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeCollection` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection` in global refactoring effort
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\DatasetEntityTypeEntity` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity` in global refactoring effort
- Change class name from `\Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor` in global refactoring effort
- Change a method name in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity::getEntityType` in global refactoring effort
- Change a method name in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity::getEntityType` in global refactoring effort and change method call to refactored method `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity::getEntityType`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobDefinition` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route\RouteDefinition` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` to use refactored method `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity::getEntityType`
- Change a method call in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::add` to use refactored method `\Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract::getEntityType`
- Change getter and setter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route\RouteEntity` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change getter and setter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobEntity` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition`
- Change a parameter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::__construct` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor`
- Change a parameter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::__construct` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor`
- Change a parameter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::__construct` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor`
- Change a parameter of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::__construct` to use refactored class `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor`
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::listBySourceAndEntityType` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::create` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::getIdsForTypes` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds`,  `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::createList` in global refactoring effort
- Change a parameter name of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds` in global refactoring effort

### Removed

- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::listBySourceAndEntityType` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\ReceptionRouteList::list`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteOverview::overview` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteFindByTargetsAndType::find` that are optimized for different use-cases

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
