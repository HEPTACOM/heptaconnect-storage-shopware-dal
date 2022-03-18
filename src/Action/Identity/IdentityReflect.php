<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Reflect\IdentityReflectPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityReflectActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PrimaryKeySharingMappingStruct;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Shopware\Core\Defaults;

class IdentityReflect implements IdentityReflectActionInterface
{
    public const LOOKUP_EXISTING_MAPPING_QUERY = '64211df0-e928-4fc9-87c1-09a4c03cf98a';

    public const LOOKUP_EXISTING_MAPPING_NODE_QUERY = 'f6b0f467-0a73-4e1f-ad75-d669899df133';

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
    }

    public function reflect(IdentityReflectPayload $payload): void
    {
        $targetPortalNodeKey = $payload->getPortalNodeKey();

        if (!$targetPortalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetPortalNodeKey));
        }

        $mappedEntities = $payload->getMappedDatasetEntities();

        foreach ($mappedEntities as $mappedEntity) {
            $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey();

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

        // TODO do not group by as this won't work when you feed more than one source portal node CONNECT-377
        foreach ($mappedEntities->groupByPortalNode() as $mappedEntityGroup) {
            $firstMappedEntity = $mappedEntityGroup->first();

            if (!$firstMappedEntity instanceof MappedDatasetEntityStruct) {
                continue;
            }

            /** @var PortalNodeStorageKey $sourcePortalNodeKey */
            $sourcePortalNodeKey = $firstMappedEntity->getMapping()->getPortalNodeKey();
            $sourcePortalNodeId = $sourcePortalNodeKey->getUuid();
            $mappingNodeIdsForFilter = [];

            /** @var MappedDatasetEntityStruct $mappedEntity */
            foreach ($mappedEntityGroup as $key => $mappedEntity) {
                $mappedEntity->getDatasetEntity()->unattach(PrimaryKeySharingMappingStruct::class);

                $primaryKey = $mappedEntity->getMapping()->getExternalId();
                /** @var MappingNodeStorageKey $mappingNodeKey */
                $mappingNodeKey = $mappedEntity->getMapping()->getMappingNodeKey();
                $mappingNodeId = $mappingNodeKey->getUuid();
                $index[$mappingNodeId][] = $key;

                if ($primaryKey === null) {
                    continue;
                }

                $mappingNodeIdsForFilter[] = $reflectedMappingNodes[] = $mappingNodeId;
                $createMappings[$sourcePortalNodeId . $mappingNodeId . $primaryKey] ??= [
                    'external_id' => $primaryKey,
                    'mapping_node_id' => $mappingNodeId,
                    'portal_node_id' => $sourcePortalNodeId,
                ];
            }

            if ($mappingNodeIdsForFilter !== []) {
                $filters[$sourcePortalNodeId] = $mappingNodeIdsForFilter;
            }
        }

        if ($filters === []) {
            return;
        }

        $builder = $this->getSearchExistingMappingsQueryBuilder();
        $mappingNodeExpressions = [];

        foreach ($filters as $sourcePortalNodeId => $mappingNodeIds) {
            $mappingNodeExpressions[] = $builder->expr()->andX(
                $builder->expr()->eq('portal_node.id', ':portalNode' . $sourcePortalNodeId),
                $builder->expr()->in('mapping_node.id', ':mappingNodes' . $sourcePortalNodeId),
            );
            $builder->setParameter('portalNode' . $sourcePortalNodeId, \hex2bin($sourcePortalNodeId), Type::BINARY);
            $builder->setParameter('mappingNodes' . $sourcePortalNodeId, \array_map('hex2bin', $mappingNodeIds), Connection::PARAM_STR_ARRAY);
        }

        $builder->andWhere($builder->expr()->orX(...$mappingNodeExpressions));

        /** @var array{portal_node_id: string, mapping_node_id: string, mapping_external_id: string} $mapping */
        foreach ($builder->iterateRows() as $mapping) {
            $portalNodeId = \bin2hex($mapping['portal_node_id']);
            $mappingNodeId = \bin2hex($mapping['mapping_node_id']);
            $externalId = (string) $mapping['mapping_external_id'];
            $key = $portalNodeId . $mappingNodeId . $externalId;

            unset($createMappings[$key]);
        }

        if ($createMappings !== []) {
            try {
                $this->connection->transactional(function () use ($createMappings): void {
                    $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

                    foreach ($createMappings as $createMapping) {
                        $createMapping['id'] = Id::randomBinary();
                        $createMapping['created_at'] = $now;

                        $this->connection->insert('mapping', $createMapping, [
                            'id' => Type::BINARY,
                            'mapping_node_id' => Type::BINARY,
                            'portal_node_id' => Type::BINARY,
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
        $builder->setParameter('portalNodeId', \hex2bin($targetPortalNodeId), Type::BINARY);
        $builder->setParameter('mappingNodeIds', \array_map('hex2bin', $reflectedMappingNodes), Connection::PARAM_STR_ARRAY);

        /** @var array{mapping_node_id: string, mapping_external_id: string} $mapping */
        foreach ($builder->iterateRows() as $mapping) {
            $mappingNodeId = \bin2hex($mapping['mapping_node_id']);
            $externalId = (string) $mapping['mapping_external_id'];
            $reflectionMapping = null;

            foreach ($index[$mappingNodeId] ?? [] as $key) {
                /** @var MappedDatasetEntityStruct $mappedEntity */
                $mappedEntity = $mappedEntities[$key];

                if (!$reflectionMapping instanceof PrimaryKeySharingMappingStruct) {
                    $reflectionMapping = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getEntityType(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey(),
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
                $sourcePortalNodeKey = $mappedEntity->getMapping()->getPortalNodeKey();
                $cacheKey = \sprintf(
                    '%s;%s',
                    $sourcePortalNodeKey->getUuid(),
                    $mappedEntity->getMapping()->getExternalId()
                );

                if (!(($reflectionMappingCache[$cacheKey] ?? null) instanceof PrimaryKeySharingMappingStruct)) {
                    $reflectionMappingCache[$cacheKey] = new PrimaryKeySharingMappingStruct(
                        $mappedEntity->getMapping()->getEntityType(),
                        $mappedEntity->getMapping()->getExternalId(),
                        $mappedEntity->getMapping()->getPortalNodeKey(),
                        $mappedEntity->getMapping()->getMappingNodeKey(),
                    );
                }

                $mappedEntity->getDatasetEntity()->setPrimaryKey(null);
                $reflectionMappingCache[$cacheKey]->addOwner($mappedEntity->getDatasetEntity());
            }
        }
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
}
