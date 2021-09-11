<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\MappingPersister;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\MappingPersister\Contract\MappingPersisterContract;
use Heptacom\HeptaConnect\Storage\Base\MappingPersister\Exception\MappingConflictException;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MappingPersister extends MappingPersisterContract
{
    private EntityRepositoryInterface $mappingRepository;

    private Connection $connection;

    public function __construct(
        EntityRepositoryInterface $mappingRepository,
        Connection $connection
    ) {
        $this->mappingRepository = $mappingRepository;
        $this->connection = $connection;
    }

    public function persist(MappingPersistPayload $payload): void
    {
        $context = Context::createDefaultContext();
        $portalNodeKey = $payload->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();

        $create = $this->getCreatePayload($payload, $portalNodeId);
        $update = $this->getUpdatePayload($payload, $portalNodeId, $context);
        $delete = $this->getDeletePayload($payload, $portalNodeId, $context);

        $this->validateMappingConflicts($portalNodeId, $create, $update, $delete);

        $this->mappingRepository->create($create, $context);
        $this->mappingRepository->update([...$update, ...$delete], $context);
    }

    protected function getCreatePayload(MappingPersistPayload $payload, string $portalNodeId): array
    {
        $create = [];

        foreach ($payload->getCreateMappings() as $createMapping) {
            $mappingNodeKey = $createMapping['mappingNodeKey'] ?? null;

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                // TODO: Add warning
                continue;
            }

            $mappingNodeId = $mappingNodeKey->getUuid();

            $externalId = $createMapping['externalId'] ?? null;

            if (!\is_string($externalId)) {
                // TODO: Add warning
                continue;
            }

            $create[$mappingNodeId.$portalNodeId.$externalId] ??= [
                'id' => (string) Uuid::uuid4()->getHex(),
                'mappingNodeId' => $mappingNodeId,
                'portalNodeId' => $portalNodeId,
                'externalId' => $externalId,
            ];
        }

        return \array_values($create);
    }

    protected function getUpdatePayload(MappingPersistPayload $payload, string $portalNodeId, Context $context): array
    {
        if ($payload->getUpdateMappings() === []) {
            return [];
        }

        $update = [];
        $mappingNodes = [];

        foreach ($payload->getUpdateMappings() as $updateMapping) {
            $mappingNodeKey = $updateMapping['mappingNodeKey'] ?? null;

            if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                // TODO: Add warning
                continue;
            }

            $externalId = $updateMapping['externalId'] ?? null;

            if (!\is_string($externalId)) {
                // TODO: Add warning
                continue;
            }

            $mappingNodes[$mappingNodeKey->getUuid()] = $externalId;
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('portalNodeId', $portalNodeId))
            ->addFilter(new EqualsAnyFilter('mappingNodeId', \array_keys($mappingNodes)))
            ->addFilter(new EqualsFilter('deletedAt', null))
        ;

        $mappings = $this->mappingRepository->search($criteria, $context);

        /** @var MappingEntity $mapping */
        foreach ($mappings->getIterator() as $mapping) {
            $externalId = $mappingNodes[$mapping->getMappingNodeId()] ?? null;

            if (!\is_string($externalId)) {
                // TODO: Add warning
                continue;
            }

            unset($mappingNodes[$mapping->getMappingNodeId()]);

            $update[] = [
                'id' => $mapping->getId(),
                'mappingNodeId' => $mapping->getMappingNodeId(),
                'externalId' => $externalId,
            ];
        }

        if ($mappingNodes !== []) {
            throw new \Exception('Unable to update mapping'); // TODO: enrich message
        }

        return $update;
    }

    protected function getDeletePayload(MappingPersistPayload $payload, string $portalNodeId, Context $context): array
    {
        if ($payload->getDeleteMappings() === []) {
            return [];
        }

        $delete = [];
        $mappingNodeIds = [];

        foreach ($payload->getDeleteMappings() as $deleteMapping) {
            if (!$deleteMapping instanceof MappingNodeStorageKey) {
                // TODO: Add warning
                continue;
            }

            $mappingNodeIds[$deleteMapping->getUuid()] = true;
        }

        $deletedAt = \date_create();

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('portalNodeId', $portalNodeId))
            ->addFilter(new EqualsAnyFilter('mappingNodeId', \array_keys($mappingNodeIds)))
            ->addFilter(new EqualsFilter('deletedAt', null))
        ;

        $mappings = $this->mappingRepository->search($criteria, $context);

        /** @var MappingEntity $mapping */
        foreach ($mappings->getIterator() as $mapping) {
            unset($mappingNodeIds[$mapping->getMappingNodeId()]);

            $delete[] = [
                'id' => $mapping->getId(),
                'mappingNodeId' => $mapping->getMappingNodeId(),
                'deletedAt' => $deletedAt,
            ];
        }

        if ($mappingNodeIds !== []) {
            throw new \Exception('Unable to delete mapping'); // TODO: enrich message
        }

        return $delete;
    }

    protected function validateMappingConflicts(
        string $portalNodeId,
        array $create,
        array $update,
        array $delete
    ): void {
        $typeIds = $this->fetchTypes(\array_column([...$create, ...$update, ...$delete], 'mappingNodeId'));

        $newMappings = $this->getNewMappings($create, $update, $typeIds, $portalNodeId);
        $changedMappings = $this->getChangedMappings($update);
        $deletedMappings = $this->getDeletedMappings($delete);

        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $typeConditions = [];

        foreach ($newMappings as $typeId => $externalIds) {
            $typeParameterKey = 'typeId_'.Uuid::uuid4()->getHex();
            $externalIdParameterKey = 'externalId_'.Uuid::uuid4()->getHex();

            $typeConditions[] = $expr->and(
                $expr->eq('mappingNode.type_id', ':'.$typeParameterKey),
                $expr->in('mapping.external_id', ':'.$externalIdParameterKey)
            );

            $queryBuilder->setParameter($typeParameterKey, \hex2bin($typeId));
            $queryBuilder->setParameter(
                $externalIdParameterKey,
                \array_keys($externalIds),
                Connection::PARAM_STR_ARRAY
            );
        }

        if ($typeConditions === []) {
            return;
        }

        $queryBuilder
            ->select([
                'mapping.mapping_node_id AS mappingNodeId',
                'mapping.external_id AS externalId',
            ])
            ->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mappingNode',
                $expr->eq('mapping.mapping_node_id', 'mappingNode.id')
            )
            ->where($expr->and(
                $expr->isNull('mapping.deleted_at'),
                $expr->isNull('mappingNode.deleted_at'),
                $expr->eq('mapping.portal_node_id', ':portalNodeId'),
                $expr->or(...$typeConditions)
            ))
            ->setParameter('portalNodeId', \hex2bin($portalNodeId))
        ;

        foreach ($queryBuilder->execute()->fetchAll() as $row) {
            $mappingNodeId = \bin2hex($row['mappingNodeId']);
            $externalId = $row['externalId'];

            if (isset($changedMappings[$mappingNodeId]) &&
                !isset($changedMappings[$mappingNodeId][$externalId])) {
                // conflict will be resolved in this changeset

                continue;
            }

            if (isset($deletedMappings[$mappingNodeId])) {
                // conflict will be resolved in this changeset

                continue;
            }

            throw new MappingConflictException(\sprintf(MappingConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId), new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($mappingNodeId), $externalId);
        }
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
            ->from('heptaconnect_dataset_entity_type', 'type')
            ->innerJoin(
                'type',
                'heptaconnect_mapping_node',
                'mappingNode',
                $expr->and(
                    $expr->eq('type.id', 'mappingNode.type_id'),
                    $expr->isNull('mappingNode.deleted_at'),
                )
            )
            ->where($expr->in('mappingNode.id', ':mappingNodeIds'))
            ->setParameter(
                'mappingNodeIds',
                \array_map('hex2bin', $mappingNodeIds),
                Connection::PARAM_STR_ARRAY
            )
        ;

        $types = [];

        foreach ($queryBuilder->execute()->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $types[\bin2hex($row['mappingNodeId'])] = \bin2hex($row['typeId']);
        }

        return $types;
    }

    private function getNewMappings(array $create, array $update, array $typeIds, string $portalNodeId): array
    {
        $newMappings = [];
        $newExternalIds = [];

        foreach ([...$create, ...$update] as $operation) {
            $mappingNodeId = $operation['mappingNodeId'];
            $externalId = $operation['externalId'];
            $typeId = $typeIds[$mappingNodeId];

            if (isset($newMappings[$typeId][$externalId]) &&
                !isset($newMappings[$typeId][$externalId][$mappingNodeId])) {
                throw new MappingConflictException(\sprintf(MappingConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId), new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($mappingNodeId), $externalId);
            }

            if (isset($newExternalIds[$typeId][$mappingNodeId]) &&
                !isset($newExternalIds[$typeId][$mappingNodeId][$externalId])) {
                throw new MappingConflictException(\sprintf(MappingConflictException::FORMAT, $portalNodeId, $mappingNodeId, $externalId), new PortalNodeStorageKey($portalNodeId), new MappingNodeStorageKey($mappingNodeId), $externalId);
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
