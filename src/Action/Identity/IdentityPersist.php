<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Types\Type;
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
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class IdentityPersist implements IdentityPersistActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function persist(IdentityPersistPayload $payload): void
    {
        $portalNodeKey = $payload->getPortalNodeKey();

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
            $now = new \DateTimeImmutable();

            foreach ($mappingNodesToMerge as $mergeCommand) {
                $this->mergeMappingNodes(
                    $mergeCommand['fromMappingNodeId'],
                    $mergeCommand['intoMappingNodeId'],
                    $now
                );
            }

            $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            foreach ($create as $insert) {
                $insert['id'] = \hex2bin($insert['id']);
                $insert['mapping_node_id'] = \hex2bin($insert['mapping_node_id']);
                $insert['portal_node_id'] = \hex2bin($insert['portal_node_id']);
                $insert['created_at'] = $now;

                $this->connection->insert('heptaconnect_mapping', $insert, [
                    'id' => Type::BINARY,
                    'mapping_node_id' => Type::BINARY,
                    'portal_node_id' => Type::BINARY,
                ]);
            }

            foreach ($update as $updateData) {
                $updateData['id'] = \hex2bin($updateData['id']);
                $updateData['mapping_node_id'] = \hex2bin($updateData['mapping_node_id']);
                $updateData['updated_at'] = $now;
                $id = $updateData['id'];
                unset($updateData['id']);

                $this->connection->update('heptaconnect_mapping', $updateData, [
                    'id' => $id,
                ], [
                    'id' => Type::BINARY,
                    'mapping_node_id' => Type::BINARY,
                ]);
            }

            foreach ($delete as $updateData) {
                $updateData['id'] = \hex2bin($updateData['id']);
                $updateData['mapping_node_id'] = \hex2bin($updateData['mapping_node_id']);
                $updateData['updated_at'] = $now;
                $updateData['deleted_at'] = $now;
                $id = $updateData['id'];
                unset($updateData['id']);

                $this->connection->update('heptaconnect_mapping', $updateData, [
                    'id' => $id,
                ], [
                    'id' => Type::BINARY,
                    'mapping_node_id' => Type::BINARY,
                ]);
            }
        });
    }

    protected function getCreatePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $create = [];

        foreach ($payload->getMappingPersistPayloads() as $createMapping) {
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
                'id' => (string) Uuid::uuid4()->getHex(),
                'mapping_node_id' => $mappingNodeId,
                'portal_node_id' => $portalNodeId,
                'external_id' => $externalId,
            ];
        }

        return \array_values($create);
    }

    protected function getUpdatePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $update = [];
        $mappingNodes = [];

        foreach ($payload->getMappingPersistPayloads() as $updateMapping) {
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

        $builder = $this->connection->createQueryBuilder();
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
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));

        $builder->setParameter('portalNodeId', \hex2bin($portalNodeId));
        $builder->setParameter('mappingNodeIds', \array_map('hex2bin', \array_keys($mappingNodes)), Connection::PARAM_STR_ARRAY);
        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1643148870);
        }

        $mappings = $statement->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($mappings as $mapping) {
            $mappingId = \bin2hex($mapping['mapping_id']);
            $mappingNodeId = \bin2hex($mapping['mapping_node_id']);
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

    protected function getDeletePayload(IdentityPersistPayload $payload, string $portalNodeId): array
    {
        $delete = [];
        $mappingNodeIds = [];

        foreach ($payload->getMappingPersistPayloads() as $deleteMapping) {
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

        $builder = $this->connection->createQueryBuilder();
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
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->eq('mapping.portal_node_id', ':portalNodeId'))
            ->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));

        $builder->setParameter('portalNodeId', \hex2bin($portalNodeId));
        $builder->setParameter('mappingNodeIds', \array_map('hex2bin', \array_keys($mappingNodeIds)), Connection::PARAM_STR_ARRAY);
        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1643148871);
        }

        $mappings = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $deletedAt = \date_create();

        foreach ($mappings as $mapping) {
            $mappingId = \bin2hex($mapping['mapping_id']);
            $mappingNodeId = \bin2hex($mapping['mapping_node_id']);

            unset($mappingNodeIds[$mappingNodeId]);

            $delete[] = [
                'id' => $mappingId,
                'mapping_node_id' => $mappingNodeId,
                'deleted_at' => $deletedAt->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ];
        }

        if ($mappingNodeIds !== []) {
            throw new CreateException(1643149291, new \RuntimeException('Expected to find mappings to delete but not every mapping node that needed an delete has been found', 1643149291));
        }

        return $delete;
    }

    protected function validateMappingConflicts(
        string $portalNodeId,
        array $create,
        array $update,
        array $delete
    ): array {
        $typeIds = $this->fetchTypes(\array_column([...$create, ...$update, ...$delete], 'mapping_node_id'));

        $newMappings = $this->getNewMappings($create, $update, $typeIds, $portalNodeId);
        $changedMappings = $this->getChangedMappings($update);
        $deletedMappings = $this->getDeletedMappings($delete);

        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $typeConditions = [];

        foreach ($newMappings as $typeId => $externalIds) {
            $typeParameterKey = 'typeId_' . Uuid::uuid4()->getHex();
            $externalIdParameterKey = 'externalId_' . Uuid::uuid4()->getHex();

            $typeConditions[] = $expr->andX(
                $expr->eq('mappingNode.type_id', ':' . $typeParameterKey),
                $expr->in('mapping.external_id', ':' . $externalIdParameterKey)
            );

            $queryBuilder->setParameter($typeParameterKey, \hex2bin($typeId));
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
            ->where($expr->andX(
                $expr->isNull('mapping.deleted_at'),
                $expr->isNull('mappingNode.deleted_at'),
                $expr->eq('mapping.portal_node_id', ':portalNodeId'),
                $expr->orX(...$typeConditions)
            ))
            ->setParameter('portalNodeId', \hex2bin($portalNodeId))
        ;

        $mappingNodesToMerge = [];

        foreach ($queryBuilder->execute()->fetchAll() as $row) {
            $intoMappingNodeId = \bin2hex($row['mappingNodeId']);
            $externalId = $row['externalId'];
            $typeId = \bin2hex($row['typeId']);

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

            throw new IdentityConflictException(\sprintf(IdentityConflictException::FORMAT, $portalNodeId, $intoMappingNodeId, $externalId), 1643144709, new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($intoMappingNodeId), $externalId);
        }

        return $mappingNodesToMerge;
    }

    private function fetchTypes(array $mappingNodeIds): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
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
                $expr->andX(
                    $expr->eq('type.id', 'mappingNode.type_id'),
                    $expr->isNull('mappingNode.deleted_at'),
                )
            )
            ->where($expr->in('mappingNode.id', ':mappingNodeIds'))
            ->setParameter('mappingNodeIds', \array_map('hex2bin', $mappingNodeIds), Connection::PARAM_STR_ARRAY);

        $types = [];

        foreach ($queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $types[\bin2hex($row['mappingNodeId'])] = \bin2hex($row['typeId']);
        }

        return $types;
    }

    private function validateMappingNodesCanBeMerged(string $fromMappingNodeId, string $intoMappingNodeId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $hasConflict = (bool) $queryBuilder->select('1')
            ->from('heptaconnect_mapping', 'mapping')
            ->where($expr->in('mapping.mapping_node_id', ':mappingNodeIds'))
            ->groupBy('mapping.portal_node_id')
            ->having($expr->gt('COUNT(mapping.id)', 1))
            ->setParameter('mappingNodeIds', [
                $fromMappingNodeId,
                $intoMappingNodeId,
            ], Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchColumn()
        ;

        return !$hasConflict;
    }

    private function mergeMappingNodes(string $from, string $into, \DateTimeInterface $now): void
    {
        $this->connection->update('heptaconnect_mapping', [
            'mapping_node_id' => \hex2bin($into),
        ], [
            'mapping_node_id' => \hex2bin($from),
        ], [
            'mapping_node_id' => Type::BINARY,
        ]);

        $this->connection->update('heptaconnect_mapping_node', [
            'deleted_at' => $now->format(Defaults::STORAGE_DATE_TIME_FORMAT),
        ], [
            'id' => \hex2bin($from),
        ], [
            'id' => Type::BINARY,
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

            if (isset($newMappings[$typeId][$externalId])
                && !isset($newMappings[$typeId][$externalId][$mappingNodeId])) {
                throw new IdentityConflictException(\sprintf(IdentityConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId), 1643144707, new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($mappingNodeId), $externalId);
            }

            if (isset($newExternalIds[$typeId][$mappingNodeId])
                && !isset($newExternalIds[$typeId][$mappingNodeId][$externalId])) {
                throw new IdentityConflictException(\sprintf(IdentityConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId), 1643144708, new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($mappingNodeId), $externalId);
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
            $mappingNodeId = $operation['mappingNodeId'];
            $externalId = $operation['externalId'];

            $changedMappings[$mappingNodeId][$externalId] = true;
        }

        return $changedMappings;
    }

    private function getDeletedMappings(array $delete): array
    {
        $deletedMappings = [];

        foreach ($delete as $operation) {
            $mappingNodeId = $operation['mappingNodeId'];

            $deletedMappings[$mappingNodeId] = true;
        }

        return $deletedMappings;
    }
}
