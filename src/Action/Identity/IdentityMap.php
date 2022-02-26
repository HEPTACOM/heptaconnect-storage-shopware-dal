<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class IdentityMap implements IdentityMapActionInterface
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityTypeAccessor $entityTypeAccessor;

    private Connection $connection;

    private int $mappingNodeQueryFallbackPageSize;

    private int $mappingQueryFallbackPageSize;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityTypeAccessor $entityTypeAccessor,
        Connection $connection,
        int $mappingNodeQueryFallbackPageSize,
        int $mappingQueryFallbackPageSize
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityTypeAccessor = $entityTypeAccessor;
        $this->connection = $connection;
        $this->mappingNodeQueryFallbackPageSize = $mappingNodeQueryFallbackPageSize;
        $this->mappingQueryFallbackPageSize = $mappingQueryFallbackPageSize;
    }

    public function map(IdentityMapPayload $payload): IdentityMapResult
    {
        $portalNodeKey = $payload->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $datasetEntities = \iterable_to_array($payload->getEntityCollection());
        $neededTypes = \array_map('get_class', $datasetEntities);
        $typeIds = $this->entityTypeAccessor->getIdsForTypes($neededTypes);

        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
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
                'type_id' => \hex2bin($typeId),
                'origin_portal_node_id' => \hex2bin($portalNodeId),
                'created_at' => $now,
                'mapping' => [
                    'external_id' => $primaryKey,
                    'portal_node_id' => \hex2bin($portalNodeId),
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

                    $mappingNodeId = \bin2hex((string) $mappingNode['mapping_node_id']);
                    $resultMappings[$key] = new Mapping(
                        $mappingExternalId,
                        $portalNodeKey,
                        new MappingNodeStorageKey($mappingNodeId),
                        $mappingNodeType
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
                $createMappingNodes[$key]['id'] = \hex2bin($mappingNodeId);
                $readMappings[$key] = $mappingNodeId;
            }

            try {
                $this->connection->transactional(function () use ($createMappingNodes): void {
                    // TODO batch
                    foreach ($createMappingNodes as $insert) {
                        $mapping = $insert['mapping'];
                        $mapping['id'] = Uuid::uuid4()->getBytes();
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
                $mappingNodeId = \bin2hex((string) $mappingNode['mapping_node_id']);

                foreach ($readMappingNodesIndex[$mappingNodeType][$mappingExternalId] ?? [] as $key) {
                    $resultMappings[$key] = new Mapping(
                        $mappingExternalId,
                        $portalNodeKey,
                        new MappingNodeStorageKey($mappingNodeId),
                        $mappingNodeType
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

    private function getMappingNodes(array $readMappingNodes, array $typeIds, string $portalNodeId): iterable
    {
        $builder = new QueryBuilder($this->connection);
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
            $builder->setParameter('typeId', \hex2bin($typeId), Types::BINARY);
            $builder->setParameter('portalNodeId', \hex2bin($portalNodeId), Types::BINARY);
            $builder->setParameter('externalIds', $externalIds, Connection::PARAM_STR_ARRAY);

            yield from $builder->fetchAssocPaginated($this->mappingNodeQueryFallbackPageSize);
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

        $builder = new QueryBuilder($this->connection);
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

        $builder->setParameter('portalNodeId', \hex2bin($portalNodeId), Types::BINARY);
        $builder->setParameter('mappingNodeIds', \array_map('hex2bin', $mappingNodeIds), Connection::PARAM_STR_ARRAY);

        return $builder->fetchAssocPaginated($this->mappingQueryFallbackPageSize);
    }
}
