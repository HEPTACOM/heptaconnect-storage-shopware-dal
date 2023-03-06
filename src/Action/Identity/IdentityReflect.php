<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Reflect\IdentityReflectPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityReflectActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Ramsey\Uuid\Uuid;

final class IdentityReflect implements IdentityReflectActionInterface
{
    public const LOOKUP_EXISTING_MAPPING_QUERY = '64211df0-e928-4fc9-87c1-09a4c03cf98a';

    public const LOOKUP_EXISTING_MAPPING_NODE_QUERY = 'f6b0f467-0a73-4e1f-ad75-d669899df133';

    public const LOOKUP_DIRECTIONAL_MAPPING_QUERY = '315e9e8f-b1b7-4e39-a42b-4dbdf3d8b14c';

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
    }

    public function reflect(IdentityReflectPayload $payload): void
    {
        $payload = $this->reflectUnidirectional($payload);
        $this->reflectBidirectional($payload);
    }

    private function reflectBidirectional(IdentityReflectPayload $payload): void
    {
        $targetPortalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$targetPortalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetPortalNodeKey));
        }

        $mappedEntities = $payload->getMappedDatasetEntities();

        foreach ($mappedEntities as $mappedEntity) {
            $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias();

            if (!$sourcePortalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($sourcePortalNodeKey));
            }

            $mappingNodeKey = $mappedEntity->getMapping()->getMappingNodeKey();

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
            }
        }

        $index = [];
        $filters = [];
        $createMappings = [];
        $reflectedMappingNodes = [];

        foreach ($mappedEntities as $key => $mappedEntity) {
            /** @var PortalNodeStorageKey $sourcePortalNodeKey */
            $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias();
            $sourcePortalNodeId = $sourcePortalNodeKey->getUuid();

            $mappedEntity->getDatasetEntity()->detachByType(PrimaryKeySharingMappingStruct::class);

            $primaryKey = $mappedEntity->getMapping()->getExternalId();
            /** @var MappingNodeStorageKey $mappingNodeKey */
            $mappingNodeKey = $mappedEntity->getMapping()->getMappingNodeKey();
            $mappingNodeId = $mappingNodeKey->getUuid();
            $index[$mappingNodeId][] = $key;

            if ($primaryKey === null) {
                continue;
            }

            $filters[$sourcePortalNodeId][] = $reflectedMappingNodes[] = $mappingNodeId;
            $createMappings[$sourcePortalNodeId . $mappingNodeId . $primaryKey] ??= [
                'external_id' => $primaryKey,
                'mapping_node_id' => Id::toBinary($mappingNodeId),
                'portal_node_id' => Id::toBinary($sourcePortalNodeId),
            ];
        }

        if ($filters === []) {
            return;
        }

        $builder = $this->getSearchExistingMappingsQueryBuilder();
        $mappingNodeExpressions = [];

        foreach ($filters as $sourcePortalNodeId => $mappingNodeIds) {
            $mappingNodeExpressions[] = $builder->expr()->and(
                $builder->expr()->eq('portal_node.id', ':portalNode' . $sourcePortalNodeId),
                $builder->expr()->in('mapping_node.id', ':mappingNodes' . $sourcePortalNodeId),
            );
            $builder->setParameter('portalNode' . $sourcePortalNodeId, Id::toBinary($sourcePortalNodeId), Types::BINARY);
            $builder->setParameter('mappingNodes' . $sourcePortalNodeId, Id::toBinaryList($mappingNodeIds), Connection::PARAM_STR_ARRAY);
        }

        $builder->andWhere($builder->expr()->or(...$mappingNodeExpressions));

        /** @var array{portal_node_id: string, mapping_node_id: string, mapping_external_id: string} $mapping */
        foreach ($builder->iterateRows() as $mapping) {
            $portalNodeId = Id::toHex($mapping['portal_node_id']);
            $mappingNodeId = Id::toHex($mapping['mapping_node_id']);
            $externalId = (string) $mapping['mapping_external_id'];
            $key = $portalNodeId . $mappingNodeId . $externalId;

            unset($createMappings[$key]);
        }

        if ($createMappings !== []) {
            try {
                $this->connection->transactional(function () use ($createMappings): void {
                    $now = DateTime::nowToStorage();

                    foreach ($createMappings as $createMapping) {
                        $createMapping['id'] = Id::randomBinary();
                        $createMapping['created_at'] = $now;

                        $this->connection->insert('heptaconnect_mapping', $createMapping, [
                            'id' => Types::BINARY,
                            'mapping_node_id' => Types::BINARY,
                            'portal_node_id' => Types::BINARY,
                        ]);
                    }
                });
            } catch (\Throwable $e) {
                throw new CreateException(1643746495, $e);
            }
        }

        $targetPortalNodeId = $targetPortalNodeKey->getUuid();
        $builder = $this->getSearchExistingMappingNodesQueryBuilder();

        $builder->andWhere($builder->expr()->eq('portal_node.id', ':portalNodeId'));
        $builder->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));
        $builder->setParameter('portalNodeId', Id::toBinary($targetPortalNodeId), Types::BINARY);
        $builder->setParameter('mappingNodeIds', Id::toBinaryList($reflectedMappingNodes), Connection::PARAM_STR_ARRAY);

        /** @var array{mapping_node_id: string, mapping_external_id: string} $mapping */
        foreach ($builder->iterateRows() as $mapping) {
            $mappingNodeId = Id::toHex($mapping['mapping_node_id']);
            $externalId = (string) $mapping['mapping_external_id'];
            $reflectionMapping = null;

            foreach ($index[$mappingNodeId] ?? [] as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                if (!$reflectionMapping instanceof PrimaryKeySharingMappingStruct) {
                    $reflectionMapping = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getEntityType(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias(),
                        $mappedEntity->getMapping()->getMappingNodeKey()
                    );

                    $reflectionMapping->setForeignKey($externalId);
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey($externalId);
                $reflectionMapping->addOwner($mappedEntity->getDatasetEntity());
            }

            unset($index[$mappingNodeId]);
        }

        foreach ($index as $keys) {
            /** @var PrimaryKeySharingMappingStruct[] $reflectionMappingCache */
            $reflectionMappingCache = [];

            foreach ($keys as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];
                /** @var PortalNodeStorageKey $sourcePortalNodeKey */
                $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias();
                $cacheKey = \sprintf(
                    '%s;%s',
                    $sourcePortalNodeKey->getUuid(),
                    $mappedEntity->getMapping()->getExternalId()
                );

                if (!(($reflectionMappingCache[$cacheKey] ?? null) instanceof PrimaryKeySharingMappingStruct)) {
                    $reflectionMappingCache[$cacheKey] = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getEntityType(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias(),
                        $mappedEntity->getMapping()->getMappingNodeKey(),
                    );
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey(null);
                $reflectionMappingCache[$cacheKey]->addOwner($mappedEntity->getDatasetEntity());
            }
        }
    }

    private function reflectUnidirectional(IdentityReflectPayload $payload): IdentityReflectPayload
    {
        $targetPortalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$targetPortalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetPortalNodeKey));
        }

        $identities = [];

        /** @var MappedDatasetEntityStruct $mappedDatasetEntity */
        foreach ($payload->getMappedDatasetEntities() as $mappedDatasetEntity) {
            $sourcePortalNodeKey = $mappedDatasetEntity->getMapping()->getPortalNodeKey()->withoutAlias();

            if (!$sourcePortalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($sourcePortalNodeKey));
            }

            $entityType = $mappedDatasetEntity->getMapping()->getEntityType();
            $sourceExternalId = $mappedDatasetEntity->getMapping()->getExternalId();

            $identities[$sourcePortalNodeKey->getUuid()][$entityType][$sourceExternalId][] = $mappedDatasetEntity;
        }

        if ($identities === []) {
            return $payload;
        }

        $queryBuilder = $this->getSearchDirectionalMappingsQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder->setParameter(
            'targetPortalNode',
            Id::toBinary($targetPortalNodeKey->getUuid()),
            ParameterType::BINARY
        );

        $conditions = [];

        foreach ($identities as $sourcePortalNodeKey => $identitiesByType) {
            foreach ($identitiesByType as $entityType => $externalIds) {
                $aliasSourcePortalNode = 'param_' . Uuid::uuid4()->getHex();
                $aliasEntityType = 'param_' . Uuid::uuid4()->getHex();
                $aliasSourceExternalIds = 'param_' . Uuid::uuid4()->getHex();

                $conditions[] = $expr->and(
                    $expr->eq('mapping.source_portal_node_id', ':' . $aliasSourcePortalNode),
                    $expr->eq('type.type', ':' . $aliasEntityType),
                    $expr->in('mapping.source_external_id', ':' . $aliasSourceExternalIds)
                );

                $queryBuilder->setParameter(
                    $aliasSourcePortalNode,
                    Id::toBinary($sourcePortalNodeKey),
                    ParameterType::BINARY
                );

                $queryBuilder->setParameter(
                    $aliasEntityType,
                    $entityType,
                    ParameterType::STRING
                );

                $queryBuilder->setParameter(
                    $aliasSourceExternalIds,
                    \array_map('strval', \array_keys($externalIds)),
                    Connection::PARAM_STR_ARRAY
                );
            }
        }

        $queryBuilder->andWhere($expr->or(...$conditions));

        /** @var MappingInterface[] $reflectionMappings */
        $reflectionMappings = [];

        foreach ($queryBuilder->execute()->fetchAllAssociative() as $mapping) {
            $sourcePortalNodeId = Id::toHex($mapping['source_portal_node_id']);
            $entityType = (string) $mapping['type'];
            $sourceExternalId = (string) $mapping['source_external_id'];
            $targetExternalId = (string) $mapping['target_external_id'];

            /** @var MappedDatasetEntityStruct $mappedEntity */
            foreach ($identities[$sourcePortalNodeId][$entityType][$sourceExternalId] ?? [] as $mappedEntity) {
                $cacheKey = \sprintf(
                    '%s;%s;%s',
                    $sourcePortalNodeId,
                    $entityType,
                    $sourceExternalId
                );

                if (!($reflectionMappings[$cacheKey] ?? null) instanceof PrimaryKeySharingMappingStruct) {
                    $reflectionMappings[$cacheKey] = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getEntityType(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey()->withoutAlias(),
                        $mappedEntity->getMapping()->getMappingNodeKey(),
                    );

                    $reflectionMappings[$cacheKey]->setForeignKey($targetExternalId);
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey($targetExternalId);
                $reflectionMappings[$cacheKey]->addOwner($mappedEntity->getDatasetEntity());
            }
        }

        $remainingMappedDatasetEntities = $payload->getMappedDatasetEntities()->filter(function (
            MappedDatasetEntityStruct $mappedDatasetEntity
        ) use (
            $reflectionMappings
        ) {
            $mappingNodeKey = $mappedDatasetEntity->getMapping()->getMappingNodeKey();

            foreach ($reflectionMappings as $reflectionMapping) {
                if ($reflectionMapping->getMappingNodeKey()->equals($mappingNodeKey)) {
                    return false;
                }
            }

            return true;
        });

        $remainingPayload = clone $payload;

        $remainingPayload->setMappedDatasetEntities(
            new MappedDatasetEntityCollection($remainingMappedDatasetEntities)
        );

        return $remainingPayload;
    }

    private function getSearchExistingMappingsQueryBuilder(): QueryBuilder
    {
        $result = $this->queryFactory->createBuilder(self::LOOKUP_EXISTING_MAPPING_QUERY);

        $result->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_portal_node',
                'portal_node',
                $result->expr()->eq('mapping.portal_node_id', 'portal_node.id')
            )
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $result->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->select([
                'portal_node.id portal_node_id',
                'mapping_node.id mapping_node_id',
                'mapping.external_id mapping_external_id',
            ])
            ->addOrderBy('mapping.id')
            ->andWhere($result->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($result->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($result->expr()->isNull('mapping.deleted_at'));

        return $result;
    }

    private function getSearchExistingMappingNodesQueryBuilder(): QueryBuilder
    {
        $result = $this->queryFactory->createBuilder(self::LOOKUP_EXISTING_MAPPING_NODE_QUERY);

        $result->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_portal_node',
                'portal_node',
                $result->expr()->eq('mapping.portal_node_id', 'portal_node.id')
            )
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $result->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->innerJoin(
                'mapping_node',
                'heptaconnect_entity_type',
                'entity_type',
                $result->expr()->eq('mapping_node.type_id', 'entity_type.id')
            )
            ->select([
                'mapping_node.id mapping_node_id',
                'mapping.external_id mapping_external_id',
            ])
            ->addOrderBy('mapping.id')
            ->andWhere($result->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($result->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($result->expr()->isNull('mapping.deleted_at'));

        return $result;
    }

    private function getSearchDirectionalMappingsQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->queryFactory->createBuilder(self::LOOKUP_DIRECTIONAL_MAPPING_QUERY);
        $expr = $queryBuilder->expr();

        $queryBuilder->from('heptaconnect_identity_redirect', 'mapping');

        $queryBuilder->select([
            'mapping.source_portal_node_id',
            'type.type',
            'mapping.source_external_id',
            'mapping.target_external_id',
        ]);

        $queryBuilder->innerJoin(
            'mapping',
            'heptaconnect_entity_type',
            'type',
            $expr->eq('mapping.type_id', 'type.id')
        );

        $queryBuilder->where(
            $expr->eq('mapping.target_portal_node_id', ':targetPortalNode')
        );

        return $queryBuilder;
    }
}
