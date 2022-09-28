<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Exception\IdentityConflictException;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistDeletePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Persist\IdentityPersistUpdatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityPersistActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityPersist implements IdentityPersistActionInterface
{
    public const TYPE_LOOKUP_QUERY = '4adbdc58-1ec7-45c0-9a5b-0ac983460505';

    public const BUILD_DELETE_PAYLOAD_QUERY = 'db92d189-494e-4d0b-be0b-492e4ded99c1';

    public const BUILD_UPDATE_PAYLOAD_QUERY = 'ddad865c-0608-42cd-89f1-148a44ed8f31';

    public const VALIDATE_CONFLICTS_QUERY = '38d26bce-b577-4def-9fe3-d055cb63495d';

    public const VALIDATE_MERGE_QUERY = 'd8bb9156-edcc-4b1b-8e7e-fae2e8932434';

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
    }

    public function persist(IdentityPersistPayload $payload): void
    {
        $portalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();

        $create = $this->getCreatePayload($payload, $portalNodeId);
        $update = $this->getUpdatePayload($payload, $portalNodeId);
        $delete = $this->getDeletePayload($payload, $portalNodeId);

        $mappingNodesToMerge = $this->validateMappingConflicts($portalNodeId, $create, $update, $delete);

        foreach ($mappingNodesToMerge as $mergeCommand) {
            foreach ($create as $key => $createCommand) {
                if ($createCommand['mapping_node_id'] === $mergeCommand['fromMappingNodeId']) {
                    unset($create[$key]);

                    break;
                }
            }
        }

        $this->connection->transactional(function () use (
            $mappingNodesToMerge,
            $create,
            $update,
            $delete
        ): void {
            $now = DateTime::nowToStorage();

            foreach ($mappingNodesToMerge as $mergeCommand) {
                $this->mergeMappingNodes(
                    $mergeCommand['fromMappingNodeId'],
                    $mergeCommand['intoMappingNodeId'],
                    $now
                );
            }

            foreach ($create as $insert) {
                $insert['id'] = Id::toBinary($insert['id']);
                $insert['mapping_node_id'] = Id::toBinary($insert['mapping_node_id']);
                $insert['portal_node_id'] = Id::toBinary($insert['portal_node_id']);
                $insert['created_at'] = $now;

                $this->connection->insert('heptaconnect_mapping', $insert, [
                    'id' => Types::BINARY,
                    'mapping_node_id' => Types::BINARY,
                    'portal_node_id' => Types::BINARY,
                ]);
            }

            foreach ($update as $updateData) {
                $updateData['id'] = Id::toBinary($updateData['id']);
                $updateData['mapping_node_id'] = Id::toBinary($updateData['mapping_node_id']);
                $updateData['updated_at'] = $now;
                $id = $updateData['id'];
                unset($updateData['id']);

                $this->connection->update('heptaconnect_mapping', $updateData, [
                    'id' => $id,
                ], [
                    'id' => Types::BINARY,
                    'mapping_node_id' => Types::BINARY,
                ]);
            }

            foreach ($delete as $updateData) {
                $updateData['id'] = Id::toBinary($updateData['id']);
                $updateData['mapping_node_id'] = Id::toBinary($updateData['mapping_node_id']);
                $updateData['updated_at'] = $now;
                $updateData['deleted_at'] = $now;
                $id = $updateData['id'];
                unset($updateData['id']);

                $this->connection->update('heptaconnect_mapping', $updateData, [
                    'id' => $id,
                ], [
                    'id' => Types::BINARY,
                    'mapping_node_id' => Types::BINARY,
                ]);
            }
        });
    }

    private function getCreatePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $create = [];

        foreach ($payload->getIdentityPersistPayloads() as $createMapping) {
            if (!$createMapping instanceof IdentityPersistCreatePayload) {
                continue;
            }

            $mappingNodeKey = $createMapping->getMappingNodeKey() ?? null;

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new InvalidCreatePayloadException($createMapping, 1643149115, new UnsupportedStorageKeyException(\get_class($mappingNodeKey)));
            }

            $mappingNodeId = $mappingNodeKey->getUuid();
            $externalId = $createMapping->getExternalId();
            $create[$mappingNodeId . $portalNodeId . $externalId] ??= [
                'id' => Id::randomHex(),
                'mapping_node_id' => $mappingNodeId,
                'portal_node_id' => $portalNodeId,
                'external_id' => $externalId,
            ];
        }

        return \array_values($create);
    }

    private function getUpdatePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $update = [];
        $mappingNodes = [];

        foreach ($payload->getIdentityPersistPayloads() as $updateMapping) {
            if (!$updateMapping instanceof IdentityPersistUpdatePayload) {
                continue;
            }

            $mappingNodeKey = $updateMapping->getMappingNodeKey();

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new InvalidCreatePayloadException($updateMapping, 1643149116, new UnsupportedStorageKeyException(\get_class($mappingNodeKey)));
            }

            $mappingNodes[$mappingNodeKey->getUuid()] = $updateMapping->getExternalId();
        }

        if ($mappingNodes === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::BUILD_UPDATE_PAYLOAD_QUERY);
        $builder
            ->from('heptaconnect_mapping', 'mapping')
            ->select([
                'mapping.id mapping_id',
                'mapping_node.id mapping_node_id',
            ])
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->addOrderBy('mapping.id')
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));

        $builder->setParameter('portalNodeId', Id::toBinary($portalNodeId));
        $builder->setParameter('mappingNodeIds', Id::toBinaryList(\array_keys($mappingNodes)), Connection::PARAM_STR_ARRAY);

        foreach ($builder->iterateRows() as $mapping) {
            $mappingId = Id::toHex($mapping['mapping_id']);
            $mappingNodeId = Id::toHex($mapping['mapping_node_id']);
            $externalId = $mappingNodes[$mappingNodeId] ?? null;

            if (!\is_string($externalId)) {
                continue;
            }

            unset($mappingNodes[$mappingNodeId]);

            $update[] = [
                'id' => $mappingId,
                'mapping_node_id' => $mappingNodeId,
                'external_id' => $externalId,
            ];
        }

        if ($mappingNodes !== []) {
            throw new CreateException(1643149290, new \RuntimeException('Expected to find mappings to update but not every mapping node that needed an update has been found', 1643149290));
        }

        return $update;
    }

    private function getDeletePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $delete = [];
        $mappingNodeIds = [];

        foreach ($payload->getIdentityPersistPayloads() as $deleteMapping) {
            if (!$deleteMapping instanceof IdentityPersistDeletePayload) {
                continue;
            }

            $mappingNodeKey = $deleteMapping->getMappingNodeKey();

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                throw new InvalidCreatePayloadException($deleteMapping, 1643149117, new UnsupportedStorageKeyException(\get_class($mappingNodeKey)));
            }

            $mappingNodeIds[$mappingNodeKey->getUuid()] = true;
        }

        if ($mappingNodeIds === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::BUILD_DELETE_PAYLOAD_QUERY);
        $builder
            ->from('heptaconnect_mapping', 'mapping')
            ->select([
                'mapping.id mapping_id',
                'mapping_node.id mapping_node_id',
            ])
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->addOrderBy('mapping.id')
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));

        $builder->setParameter('portalNodeId', Id::toBinary($portalNodeId));
        $builder->setParameter('mappingNodeIds', Id::toBinaryList(\array_keys($mappingNodeIds)), Connection::PARAM_STR_ARRAY);

        foreach ($builder->iterateRows() as $mapping) {
            $mappingId = Id::toHex($mapping['mapping_id']);
            $mappingNodeId = Id::toHex($mapping['mapping_node_id']);

            unset($mappingNodeIds[$mappingNodeId]);

            $delete[] = [
                'id' => $mappingId,
                'mapping_node_id' => $mappingNodeId,
            ];
        }

        if ($mappingNodeIds !== []) {
            throw new CreateException(1643149291, new \RuntimeException('Expected to find mappings to delete but not every mapping node that needed an delete has been found', 1643149291));
        }

        return $delete;
    }

    private function validateMappingConflicts(
        string $portalNodeId,
        array $create,
        array $update,
        array $delete
    ): array {
        $typeIds = $this->fetchTypes(\array_column([...$create, ...$update, ...$delete], 'mapping_node_id'));

        $newMappings = $this->getNewMappings($create, $update, $typeIds, $portalNodeId);
        $changedMappings = $this->getChangedMappings($update);
        $deletedMappings = $this->getDeletedMappings($delete);

        $queryBuilder = $this->queryFactory->createBuilder(self::VALIDATE_CONFLICTS_QUERY);
        $expr = $queryBuilder->expr();

        $typeConditions = [];

        foreach ($newMappings as $typeId => $externalIds) {
            $typeParameterKey = 'typeId_' . Id::randomHex();
            $externalIdParameterKey = 'externalId_' . Id::randomHex();

            $typeConditions[] = $expr->and(
                $expr->eq('mappingNode.type_id', ':' . $typeParameterKey),
                $expr->in('mapping.external_id', ':' . $externalIdParameterKey)
            );

            $queryBuilder->setParameter($typeParameterKey, Id::toBinary($typeId));
            $queryBuilder->setParameter(
                $externalIdParameterKey,
                \array_keys($externalIds),
                Connection::PARAM_STR_ARRAY
            );
        }

        if ($typeConditions === []) {
            return [];
        }

        $queryBuilder
            ->select([
                'mapping.mapping_node_id AS mappingNodeId',
                'mapping.external_id AS externalId',
                'mappingNode.type_id AS typeId',
            ])
            ->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mappingNode',
                $expr->eq('mapping.mapping_node_id', 'mappingNode.id')
            )
            ->addOrderBy('mapping.id')
            ->where($expr->and(
                $expr->isNull('mapping.deleted_at'),
                $expr->isNull('mappingNode.deleted_at'),
                $expr->eq('mapping.portal_node_id', ':portalNodeId'),
                $expr->or(...$typeConditions)
            ))
            ->setParameter('portalNodeId', Id::toBinary($portalNodeId))
        ;

        $mappingNodesToMerge = [];

        foreach ($queryBuilder->iterateRows() as $row) {
            $intoMappingNodeId = Id::toHex($row['mappingNodeId']);
            $externalId = $row['externalId'];
            $typeId = Id::toHex($row['typeId']);

            if (isset($changedMappings[$intoMappingNodeId])
                && !isset($changedMappings[$intoMappingNodeId][$externalId])) {
                // conflict will be resolved in this changeset

                continue;
            }

            if (isset($deletedMappings[$intoMappingNodeId])) {
                // conflict will be resolved in this changeset

                continue;
            }

            $fromMappingNodeId = \array_key_first($newMappings[$typeId][$externalId]);

            if ($this->validateMappingNodesCanBeMerged($fromMappingNodeId, $intoMappingNodeId)) {
                $mappingNodesToMerge[] = [
                    'fromMappingNodeId' => $fromMappingNodeId,
                    'intoMappingNodeId' => $intoMappingNodeId,
                ];

                continue;
            }

            // instructed identity mapping cannot be performed as related identities conflict
            throw new IdentityConflictException(
                \sprintf(IdentityConflictException::FORMAT, $portalNodeId, $intoMappingNodeId, $externalId),
                1643144709,
                new PortalNodeStorageKey($portalNodeId),
                new MappingNodeStorageKey($intoMappingNodeId),
                $externalId
            );
        }

        return $mappingNodesToMerge;
    }

    private function fetchTypes(array $mappingNodeIds): array
    {
        $queryBuilder = $this->queryFactory->createBuilder(self::TYPE_LOOKUP_QUERY);
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->select([
                'mappingNode.id AS mappingNodeId',
                'type.id AS typeId',
            ])
            ->from('heptaconnect_entity_type', 'type')
            ->innerJoin(
                'type',
                'heptaconnect_mapping_node',
                'mappingNode',
                (string) $expr->and(
                    $expr->eq('type.id', 'mappingNode.type_id'),
                    $expr->isNull('mappingNode.deleted_at'),
                )
            )
            ->addOrderBy('mappingNode.id')
            ->where($expr->in('mappingNode.id', ':mappingNodeIds'))
            ->setParameter('mappingNodeIds', Id::toBinaryList($mappingNodeIds), Connection::PARAM_STR_ARRAY);

        $types = [];

        foreach ($queryBuilder->iterateRows() as $row) {
            $types[Id::toHex($row['mappingNodeId'])] = Id::toHex($row['typeId']);
        }

        return $types;
    }

    private function validateMappingNodesCanBeMerged(string $fromMappingNodeId, string $intoMappingNodeId): bool
    {
        $queryBuilder = $this->queryFactory->createBuilder(self::VALIDATE_MERGE_QUERY);
        $expr = $queryBuilder->expr();

        $hasConflict = (bool) $queryBuilder->select('1')
            ->from('heptaconnect_mapping', 'mapping')
            ->addOrderBy('mapping.id')
            ->where($expr->and(
                $expr->in('mapping.mapping_node_id', ':mappingNodeIds'),
                $expr->isNull('mapping.deleted_at')
            ))
            ->groupBy('mapping.portal_node_id')
            ->having($expr->gt('COUNT(mapping.id)', 1))
            ->setParameter('mappingNodeIds', Id::toBinaryList([
                $fromMappingNodeId,
                $intoMappingNodeId,
            ]), Connection::PARAM_STR_ARRAY)
            ->fetchSingleValue();

        return !$hasConflict;
    }

    private function mergeMappingNodes(string $from, string $into, string $now): void
    {
        $this->connection->update('heptaconnect_mapping', [
            'mapping_node_id' => Id::toBinary($into),
        ], [
            'mapping_node_id' => Id::toBinary($from),
        ], [
            'mapping_node_id' => Types::BINARY,
        ]);

        $this->connection->update('heptaconnect_mapping_node', [
            'deleted_at' => $now,
        ], [
            'id' => Id::toBinary($from),
        ], [
            'id' => Types::BINARY,
        ]);
    }

    private function getNewMappings(array $create, array $update, array $typeIds, string $portalNodeId): array
    {
        $newMappings = [];
        $newExternalIds = [];

        foreach ([...$create, ...$update] as $operation) {
            $mappingNodeId = $operation['mapping_node_id'];
            $externalId = $operation['external_id'];
            $typeId = $typeIds[$mappingNodeId];

            $mappingNodeHasBeenFoundBefore = isset($newMappings[$typeId][$externalId]);
            $mappingNodeHasNotBeenFoundWithThisExternalId = !isset($newMappings[$typeId][$externalId][$mappingNodeId]);

            if ($mappingNodeHasBeenFoundBefore && $mappingNodeHasNotBeenFoundWithThisExternalId) {
                throw new IdentityConflictException(
                    \sprintf(IdentityConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId),
                    1643144707,
                    new PortalNodeStorageKey($portalNodeId),
                    new MappingNodeStorageKey($mappingNodeId),
                    $externalId
                );
            }

            $externalIdHasBeenFoundBefore = isset($newExternalIds[$typeId][$mappingNodeId]);
            $externalIdHasNotBeenFoundWithThisMappingNode = !isset($newExternalIds[$typeId][$mappingNodeId][$externalId]);

            if ($externalIdHasBeenFoundBefore && $externalIdHasNotBeenFoundWithThisMappingNode) {
                throw new IdentityConflictException(
                    \sprintf(IdentityConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId),
                    1643144708,
                    new PortalNodeStorageKey($portalNodeId),
                    new MappingNodeStorageKey($mappingNodeId),
                    $externalId
                );
            }

            $newMappings[$typeId][$externalId][$mappingNodeId] = true;
            $newExternalIds[$typeId][$mappingNodeId][$externalId] = true;
        }

        return $newMappings;
    }

    private function getChangedMappings(array $update): array
    {
        $changedMappings = [];

        foreach ($update as $operation) {
            $mappingNodeId = $operation['mapping_node_id'];
            $externalId = $operation['external_id'];

            $changedMappings[$mappingNodeId][$externalId] = true;
        }

        return $changedMappings;
    }

    private function getDeletedMappings(array $delete): array
    {
        $deletedMappings = [];

        foreach ($delete as $operation) {
            $mappingNodeId = $operation['mapping_node_id'];

            $deletedMappings[$mappingNodeId] = true;
        }

        return $deletedMappings;
    }
}
