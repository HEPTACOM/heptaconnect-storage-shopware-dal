# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to a variation of [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
The version numbers are structured like `GENERATION.MAJOR.MINOR.PATCH`:

* `GENERATION` version when concepts and APIs are abandoned, but brand and project name stay the same,
* `MAJOR` version when you make incompatible API changes and provide an upgrade path,
* `MINOR` version when you add functionality in a backwards compatible manner, and
* `PATCH` version when you make backwards compatible bug fixes.

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
- Add exception code `1642940744` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::getIdsForTypes` when writing to the database fails
- Add exception code `1642951892` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::map` when writing to the database fails
- Implement `\Heptacom\HeptaConnect\Storage\Base\Action\Contract\Route\Delete\RouteDeleteActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteDelete` to delete routes
- Add exception code `1643144707` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when check for same external id and having different mapping nodes fails
- Add exception code `1643144708` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when check for same mapping node and having different external ids fails
- Add exception code `1643144709` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when instructed identity mapping cannot be performed as related identities conflict
- Add exception code `1643149115` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when the create-payload refers to a mapping node with an invalid mapping node key
- Add exception code `1643149116` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when the update-payload refers to a mapping node with an invalid mapping node key
- Add exception code `1643149117` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when the delete-payload refers to a mapping node with an invalid mapping node key
- Add exception code `1643149290` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when the update-payload refers to an entry that is not present in storage
- Add exception code `1643149291` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::persist` when the delete-payload refers to an entry that is not present in storage
- Add exception code `1643746494` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect::reflect` when query execution could not return a ResultStatement
- Add exception code `1643746495` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect::reflect` when writing to the database fails
- Add exception code `1643746496` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect::reflect` when query execution could not return a ResultStatement
- Implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityOverviewActionInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview` to list identities
- Add exception code `1643877525` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview::overview` when the payload refers to a mapping node with an invalid mapping node key
- Add exception code `1643877526` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview::overview` when the payload refers to a portal node with an invalid portal node key
- Add exception code `1643877527` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview::overview` when the criteria has an invalid sorting option
- Implement `\Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade`
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchAssocPaginated` to always paginate even when no max result is given with the fallback pagination size parameter into `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::__construct`
- Add exception code `1645901521` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchAssoc` when query execution could not return a ResultStatement
- Add exception code `1645901522` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchSingleValue` when more than 1 row can be fetched from a query that expects only a single row
- Add exception code `1645901523` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchAssocSingleRow` when more than 1 row can be fetched from a query that expects only a single row
- Add exception code `1645901524` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchAssocPaginated` when an invalid fallback pagination size is given
- Add exception code `1645901525` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::fetchAssocPaginated` when the query will be paginated without order statement
- Add query identifier parameter into `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder::__construct` that is added on query execution
- Add factory `\Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory` with configurable fallback pagination size for every builder
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor::LOOKUP_QUERY` as `992a88ac-a232-4d99-b1cc-4165da81ba77` to identify a query used for looking up entity types
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor::LOOKUP_QUERY` as `28ef8980-146b-416c-8338-f1e394ac8c5f` to identify a query used for looking up job types
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::MAPPING_NODE_QUERY` as `0d104088-b0d4-4158-8f95-0bc8a6880cc8` to identify a query used for loading related mapping nodes
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityMap::MAPPING_QUERY` as `3c3f73e2-a95c-4ff3-89c5-c5f166195c24` to identify a query used for loading related mappings
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityOverview::OVERVIEW_QUERY` as `510bb5ac-4bcb-4ddf-927c-05971298bc55` to identify a query used for loading an overview page for identities
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::TYPE_LOOKUP_QUERY` as `4adbdc58-1ec7-45c0-9a5b-0ac983460505` to identify a query used for looking up related entity types
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::BUILD_DELETE_PAYLOAD_QUERY` as `db92d189-494e-4d0b-be0b-492e4ded99c1` to identify a query used for reading identities that have to be deleted
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::BUILD_UPDATE_PAYLOAD_QUERY` as `ddad865c-0608-42cd-89f1-148a44ed8f31` to identify a query used for reading identities that have be updated
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::VALIDATE_CONFLICTS_QUERY` as `38d26bce-b577-4def-9fe3-d055cb63495d` to identify a query used for identifying possible conflicts
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist::VALIDATE_MERGE_QUERY` as `d8bb9156-edcc-4b1b-8e7e-fae2e8932434` to identify a query used for identifying possible merges
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobCreate::PAYLOAD_LOOKUP_QUERY` as `b2234327-93a0-4854-ac52-fba75f71da74` to identify a query used for looking up payload entries
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete::DELETE_QUERY` as `f60b01fc-8f9a-4a37-a009-a00db9a64b11` to identify a query used for deleting jobs
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobDelete::LOOKUP_QUERY` as `c1c41a80-6aec-4499-a07a-26ee57b07594` to identify a query used for looking up jobs that can be deleted
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobFinishedList::LIST_QUERY` as `008ced6c-7517-46f8-a8a0-8f3c31b50467` to identify a query used for listing finished jobs
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job\JobGet::FETCH_QUERY` as `809ecd5e-291f-417c-9c76-003c7ead65e9` to identify a query used for reading job data
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionFind::LOOKUP_QUERY` as `82bb12c6-ed9c-4646-901a-4ff7e8e4e88c` to identify a query used for looking up portal extension configurations
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive::CLASS_NAME_LOOKUP_QUERY` as `a6bbbe3b-bf42-455d-824e-8c1aac4453b6` to identify a query used for looking up class name references
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive::ID_LOOKUP_QUERY` as `2fc478d7-4f03-4a3d-a335-d6daf4244c27` to identify a query used for looking up existing configuration ids
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension\PortalExtensionSwitchActive::SWITCH_QUERY` as `5444ccf3-cf11-4a5b-bf5f-8c268dce9c1a` to identify a query used for switching active states of portal extensions
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete::DELETE_QUERY` as `219156bb-0598-49df-8205-6d10e8f92a61` to identify a query used for deleting portal nodes
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete::LOOKUP_QUERY` as `aafca974-b95e-46ea-a680-834a93d13140` to identify a query used for looking up portal nodes that can be deleted
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeGet::FETCH_QUERY` as `efbd19ba-bc8e-412c-afb2-8a21f35e21f9` to identify a query used for reading portal node data
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeList::LIST_QUERY` as `52e85ba9-3610-403b-be28-b8d138481ace` to identify a query used for listing up all portal nodes
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\ReceptionRouteList::LIST_QUERY` as `a2dc9481-5738-448a-9c85-617fec45a00d` to identify a query used for listing up all routes that are configured for reception
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteDelete::LOOKUP_QUERY` as `b270142d-c897-4d1d-bddb-7641fbfb95a2` to identify a query used for looking up routes to delete
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteDelete::DELETE_QUERY` as `384f50ca-1e0a-464b-80fd-824fc83b87ca` to identify a query used for deleting routes
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind::LOOKUP_QUERY` as `1f0d7c11-0d1c-4834-8b15-148d826d64e8` to identify a query used for looking up routes
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteGet::FETCH_QUERY` as `24ab04cd-03f5-40c8-af25-715856281314` to identify a query used for reading route data
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteOverview::OVERVIEW_QUERY` as `6cb18ac6-6f5a-4d31-bed3-44849eb51f6f` to identify a query used for loading an overview page for route
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview::OVERVIEW_QUERY` as `329b4aa3-e576-4930-b89f-c63dca05c16e` to identify a query used for loading an overview page for route capabilities
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFind::LOOKUP_QUERY` as `6c5db7b-004d-40c8-b9cc-53707aab658b` to identify a query used for looking up HTTP handler configurations
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete::DELETE_QUERY` as `40e42cd4-4ac3-4304-8cfc-9083d37e81cd` to identity query used for deleting portal node storage entries
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete::DELETE_EXPIRED_QUERY` as `1972fcfd-5d64-4bce-a6b5-19cb6a8ad671` to identity query used for deleting expired portal node storage entries
- Add exception code `1646209690` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete::delete` when writing to the database fails
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageClear::CLEAR_QUERY` as `1087e0dc-07fe-48d7-903c-9353167c3e89` to identity query used for deleting all portal node storage entries
- Add exception code `1646209691` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageClear::clear` when writing to the database fails
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageGet::FETCH_QUERY` as `679d6e76-bb9c-410d-ac22-17c64afcb7cc` to identity query used for reading portal node storage entries
- Add exception code `1646341933` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageSet::set` when writing to the database fails
- Add `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageSet::UPDATE_PREPARATION_QUERY` as `75fada39-34f0-4e03-b3b5-141da358181d` to identity query used for reading portal node storage entries to prepare update statements

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
- Rename `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityReflect` and implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityReflectActionInterface`
- Rename `\Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister\MappingPersister` to `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity\IdentityPersist` and implement `\Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityPersistActionInterface`
- Remove exception code `1637467903` from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\RouteCapability\RouteCapabilityOverview::overview` expect exception code `1645901521` instead
- Remove exception code `1637467906` from `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route\RouteFind::find` expect exception code `1645901521` instead

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
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listByNodes` from removed contract `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract::listByNodes`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::listUnsavedExternalIds` from removed contract `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract::listUnsavedExternalIds`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingRepository::updateExternalId` from removed contract `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingRepositoryContract::updateExternalId`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::listByTypeAndPortalNodeAndExternalId` from removed contract `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract::listByTypeAndPortalNodeAndExternalId`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingNodeRepository::create` from removed contract `\Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingNodeRepositoryContract::create`
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::unset` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::deleteMultiple` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageDelete::delete` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::clear` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageClear::clear` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getValue`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getType`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::has` and `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::getMultiple` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageGet::get` that allows for optimizations for different use-cases
- Remove implementation `\Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage::set` in favour of `\Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage\PortalNodeStorageSet::set` that allows for optimizations for different use-cases

### Fixed

### Security

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

### Fixed

- Change `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\MappingExceptionRepository::create` so it includes a check for the success of `\json_encode`

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

### Deprecated

- Deprecate cronjobs to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\CronjobRunRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey`
- Deprecate webhooks to allow for new implementation at different point in time and with it `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookCollection`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookDefinition`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook\WebhookEntity`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\WebhookRepository`, `\Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey`

### Fixed

- Fix bug and improved performance on entity reflection in `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` when empty entity collection has been passed in

## [0.4.0] - 2021-07-03

### Added

- Add support for preview portal node keys `\Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey` in `\Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage::getConfiguration`

### Changed

- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityMapper::mapEntities` by restructuring database queries
- Improve performance of `\Heptacom\HeptaConnect\Storage\ShopwareDal\EntityReflector::reflectEntities` by restructuring database queries

## [0.3.1] - 2021-07-02
## [0.3.0] - 2021-07-02
