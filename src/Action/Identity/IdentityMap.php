<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Dataset\Base\EntityType;
use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingInterface;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityCollection;
use Heptacom\HeptaConnect\Portal\Base\Mapping\MappedDatasetEntityStruct;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Map\IdentityMapResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Mapping;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityMapActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityMap implements IdentityMapActionInterface
{
    public const MAPPING_NODE_QUERY = '0d104088-b0d4-4158-8f95-0bc8a6880cc8';

    public const MAPPING_QUERY = '3c3f73e2-a95c-4ff3-89c5-c5f166195c24';

    public function __construct(private StorageKeyGeneratorContract $storageKeyGenerator, private EntityTypeAccessor $entityTypeAccessor, private Connection $connection, private QueryFactory $queryFactory)
    {
    }

    public function map(IdentityMapPayload $payload): IdentityMapResult
    {
        $portalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $datasetEntities = \iterable_to_array($payload->getEntityCollection());
        /** @var class-string<DatasetEntityContract>[] $neededTypes */
        $neededTypes = \array_map('get_class', $datasetEntities);
        $typeIds = $this->entityTypeAccessor->getIdsForTypes($neededTypes);

        $now = DateTime::nowToStorage();
        $readMappingNodes = [];
        $readMappingNodesIndex = [];
        $createMappingNodes = [];
        $readMappings = [];
        /** @var MappingInterface[] $resultMappings */
        $resultMappings = [];

        /** @var DatasetEntityContract $entity */
        foreach ($datasetEntities as $key => $entity) {
            $primaryKey = $entity->getPrimaryKey();
            $type = \get_class($entity);

            if ($primaryKey === null) {
                continue;
            }

            $typeId = $typeIds[$type];
            $readMappingNodes[$key] = [
                'externalId' => $primaryKey,
                'type' => $type,
            ];

            $readMappingNodesIndex[$type][$primaryKey][] = $key;

            $createMappingNodes[$key] = [
                'type_id' => Id::toBinary($typeId),
                'origin_portal_node_id' => Id::toBinary($portalNodeId),
                'created_at' => $now,
                'mapping' => [
                    'external_id' => $primaryKey,
                    'portal_node_id' => Id::toBinary($portalNodeId),
                    'created_at' => $now,
                ],
            ];
        }

        $createMappingNodes = \array_unique($createMappingNodes, \SORT_REGULAR);

        if ($readMappingNodes !== []) {
            foreach ($this->getMappingNodes($readMappingNodes, $typeIds, $portalNodeId) as $mappingNode) {
                $mappingExternalId = (string) $mappingNode['mapping_external_id'];
                $mappingNodeType = (string) $mappingNode['mapping_node_type'];

                foreach ($readMappingNodesIndex[$mappingNodeType][$mappingExternalId] ?? [] as $key) {
                    unset($createMappingNodes[$key]);

                    $mappingNodeId = Id::toHex((string) $mappingNode['mapping_node_id']);
                    $resultMappings[$key] = new Mapping(
                        $mappingExternalId,
                        $portalNodeKey,
                        new MappingNodeStorageKey($mappingNodeId),
                        new EntityType($mappingNodeType)
                    );
                }
            }
        }

        if ($createMappingNodes !== []) {
            /** @var MappingNodeKeyInterface[] $mappingNodeKeys */
            $mappingNodeKeys = \iterable_to_array($this->storageKeyGenerator->generateKeys(
                MappingNodeKeyInterface::class,
                \count($createMappingNodes)
            ));

            foreach (\array_keys($createMappingNodes) as $key) {
                $mappingNodeKey = \array_shift($mappingNodeKeys);

                if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($mappingNodeKey));
                }

                $mappingNodeId = $mappingNodeKey->getUuid();
                $createMappingNodes[$key]['id'] = Id::toBinary($mappingNodeId);
                $readMappings[$key] = $mappingNodeId;
            }

            try {
                $this->connection->transactional(function () use ($createMappingNodes): void {
                    // TODO batch
                    foreach ($createMappingNodes as $insert) {
                        $mapping = $insert['mapping'];
                        $mapping['id'] = Id::randomBinary();
                        $mapping['mapping_node_id'] = $insert['id'];

                        unset($insert['mapping']);

                        $this->connection->insert('heptaconnect_mapping_node', $insert, [
                            'id' => Types::BINARY,
                            'type_id' => Types::BINARY,
                            'origin_portal_node_id' => Types::BINARY,
                        ]);
                        $this->connection->insert('heptaconnect_mapping', $mapping, [
                            'id' => Types::BINARY,
                            'mapping_node_id' => Types::BINARY,
                            'portal_node_id' => Types::BINARY,
                        ]);
                    }
                });
            } catch (\Throwable $throwable) {
                throw new CreateException(1642951892, $throwable);
            }
        }

        if ($readMappings !== []) {
            foreach ($this->getMappings($readMappings, $portalNodeId) as $mappingNode) {
                $mappingExternalId = (string) $mappingNode['mapping_external_id'];
                $mappingNodeType = (string) $mappingNode['mapping_node_type'];
                $mappingNodeId = Id::toHex((string) $mappingNode['mapping_node_id']);

                foreach ($readMappingNodesIndex[$mappingNodeType][$mappingExternalId] ?? [] as $key) {
                    $resultMappings[$key] = new Mapping(
                        $mappingExternalId,
                        $portalNodeKey,
                        new MappingNodeStorageKey($mappingNodeId),
                        new EntityType($mappingNodeType)
                    );
                }
            }
        }

        $result = new IdentityMapResult(new MappedDatasetEntityCollection());

        foreach ($datasetEntities as $key => $entity) {
            if (isset($resultMappings[$key])) {
                $result->getMappedDatasetEntityCollection()->push([
                    new MappedDatasetEntityStruct($resultMappings[$key], $entity),
                ]);
            }
        }

        return $result;
    }

    /**
     * @return iterable<array{mapping_node_type: string, mapping_external_id: string, mapping_node_id: string}>
     */
    private function getMappingNodes(array $readMappingNodes, array $typeIds, string $portalNodeId): iterable
    {
        $builder = $this->queryFactory->createBuilder(self::MAPPING_NODE_QUERY);
        $builder->from('heptaconnect_entity_type', 'type')
            ->innerJoin(
                'type',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping_node.type_id', 'type.id')
            )
            ->innerJoin(
                'mapping_node',
                'heptaconnect_mapping',
                'mapping',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->addOrderBy('mapping.id')
            ->select([
                'type.type mapping_node_type',
                'mapping.external_id mapping_external_id',
                'mapping_node.id mapping_node_id',
            ])
            ->andWhere($builder->expr()->eq('type.id', ':typeId'))
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping.external_id', ':externalIds'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'));

        $filtersByType = [];
        foreach ($readMappingNodes as $entity) {
            $filtersByType[$typeIds[$entity['type']]][$entity['externalId']] = true;
        }

        foreach ($filtersByType as $typeId => $externalIds) {
            $builder->setParameter('typeId', Id::toBinary($typeId), Types::BINARY);
            $builder->setParameter('portalNodeId', Id::toBinary($portalNodeId), Types::BINARY);
            $builder->setParameter('externalIds', \array_map('strval', \array_keys($externalIds)), Connection::PARAM_STR_ARRAY);

            yield from $builder->iterateRows();
        }
    }

    /**
     * @return iterable<int, array>
     */
    private function getMappings(array $mappingNodeIds, string $portalNodeId): iterable
    {
        if ($mappingNodeIds === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::MAPPING_QUERY);
        $builder->from('heptaconnect_entity_type', 'type')
            ->innerJoin(
                'type',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping_node.type_id', 'type.id')
            )
            ->innerJoin(
                'mapping_node',
                'heptaconnect_mapping',
                'mapping',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->select([
                'type.type mapping_node_type',
                'mapping.external_id mapping_external_id',
                'mapping_node.id mapping_node_id',
            ])
            ->addOrderBy('mapping_node.id')
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'));

        $builder->setParameter('portalNodeId', Id::toBinary($portalNodeId), Types::BINARY);
        $builder->setParameter('mappingNodeIds', Id::toBinaryList($mappingNodeIds), Connection::PARAM_STR_ARRAY);

        return $builder->iterateRows();
    }
}
