<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Storage\Base\Contract\MappingPersisterContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\MappingPersistPayload;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class MappingPersister extends MappingPersisterContract
{
    private EntityRepositoryInterface $mappingRepository;

    public function __construct(EntityRepositoryInterface $mappingRepository)
    {
        $this->mappingRepository = $mappingRepository;
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

            $create[] = [
                'id' => Uuid::randomHex(),
                'mappingNodeId' => $mappingNodeId,
                'portalNodeId' => $portalNodeId,
                'externalId' => $externalId,
            ];
        }

        return $create;
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

            $update[] = [
                'id' => $mapping->getId(),
                'externalId' => $externalId,
            ];
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

            $mappingNodeIds[] = $deleteMapping->getUuid();
        }

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('portalNodeId', $portalNodeId))
            ->addFilter(new EqualsAnyFilter('mappingNodeId', $mappingNodeIds))
            ->addFilter(new EqualsFilter('deletedAt', null))
        ;

        $mappingIds = $this->mappingRepository->searchIds($criteria, $context)->getIds();

        foreach ($mappingIds as $mappingId) {
            $delete[] = [
                'id' => $mappingId,
                'deletedAt' => \date_create(),
            ];
        }

        return $delete;
    }
}
