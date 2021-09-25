# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.7.0] - 2021-09-25

### Added

* Add support for composer dependency `ramsey/uuid: 4.*`
* Add implementation for `\Heptacom\HeptaConnect\Storage\Base\Contract\PortalStorageContract` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::clear`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getMultiple` and  `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::deleteMultiple` to allow PSR simple cache compatibility
* New service `\Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister` responsible for saving mappings after reception. Could improve usages of `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract`.

### Changed

* Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`
* Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`

### Fixed

* Change string comparison on database layer from whitespace-unaware, case-insensitive to binary for jobs, job payloads, mappings, portal nodes, portal node storage, data entity class names so lookups are one-to-one which therefore affects behaviour `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobPayloadRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::createList`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::updateExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository::listByClass`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::listBySourceAndEntityType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\RouteRepository::create`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\DatasetEntityTypeAccessor::getIdsForTypes`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::set`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::unset`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getValue`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::has`
* Disable HTML stripping from string columns in DAL for jobs, mappings and portal node storage so storing data will allow `<>` symbols which therefore affects behaviour `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\JobRepository::add`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByPortalNodeAndType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::updateExternalId`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::set`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::unset`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getValue`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::has`
* `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper` now respects soft-deletions of mappings and mapping nodes.
* `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector` now respects soft-deletions of mappings and mapping nodes.

## [0.6.0] - 2021-07-26

## [0.5.1] - 2021-07-13

## [0.5.0] - 2021-07-11

### Fixed

* Fixed bug and improved performance on entity reflection in `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` when empty entity collection has been passed in

### Deprecated

* Deprecated cronjobs to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRunRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey`
* Deprecated webhooks to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\WebhookRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey`

## [0.4.0] - 2021-07-03

### Added

* Added support for preview portal node keys `\Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::getConfiguration`

### Changed

* Improved performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities` by restructuring database queries
* Improved performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` by restructuring database queries

## [0.3.1] - 2021-07-02
## [0.3.0] - 2021-07-02
