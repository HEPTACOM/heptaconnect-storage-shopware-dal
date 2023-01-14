<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityDirection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Create\IdentityDirectionCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Create\IdentityDirectionCreatePayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Create\IdentityDirectionCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Create\IdentityDirectionCreateResultCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityDirection\IdentityDirectionCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\IdentityDirectionKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityDirectionStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class IdentityDirectionCreate implements IdentityDirectionCreateActionInterface
{
    private Connection $connection;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityTypeAccessor $entityTypes;

    public function __construct(
        Connection $connection,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityTypeAccessor $entityTypes
    ) {
        $this->connection = $connection;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityTypes = $entityTypes;
    }

    public function create(IdentityDirectionCreatePayloadCollection $payloads): IdentityDirectionCreateResultCollection
    {
        $entityTypes = [];

        /** @var IdentityDirectionCreatePayload $payload */
        foreach ($payloads as $payload) {
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722278, new UnsupportedStorageKeyException(\get_class($sourceKey)));
            }

            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            if (!$targetKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722279, new UnsupportedStorageKeyException(\get_class($targetKey)));
            }

            $entityTypes[] = $payload->getEntityType();
        }

        $entityTypeIds = $this->entityTypes->getIdsForTypes($entityTypes);

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                /** @var IdentityDirectionCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getEntityType() === $entityType) {
                        throw new InvalidCreatePayloadException($payload, 1673722280);
                    }
                }
            }
        }

        $keys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(IdentityDirectionKeyInterface::class, $payloads->count())));
        $now = DateTime::nowToStorage();
        $identityDirectionInserts = [];
        $result = [];

        foreach ($payloads as $payload) {
            $key = $keys->current();
            $keys->next();

            if (!$key instanceof IdentityDirectionStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722281, new UnsupportedStorageKeyException(\get_class($key)));
            }

            /** @var PortalNodeStorageKey $sourceKey */
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();
            /** @var PortalNodeStorageKey $targetKey */
            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            $identityDirectionInserts[] = [
                'id' => Id::toBinary($key->getUuid()),
                'source_portal_node_id' => Id::toBinary($sourceKey->getUuid()),
                'source_external_id' => $payload->getSourceExternalId(),
                'target_portal_node_id' => Id::toBinary($targetKey->getUuid()),
                'target_external_id' => $payload->getTargetExternalId(),
                'type_id' => Id::toBinary($entityTypeIds[$payload->getEntityType()]),
                'created_at' => $now,
            ];

            $result[] = new IdentityDirectionCreateResult($key);
        }

        try {
            $this->connection->transactional(function () use ($identityDirectionInserts): void {
                // TODO batch
                foreach ($identityDirectionInserts as $identityDirectionInsert) {
                    $this->connection->insert('heptaconnect_identity_direction', $identityDirectionInsert, [
                        'id' => Types::BINARY,
                        'portal_node_source_id' => Types::BINARY,
                        'portal_node_target_id' => Types::BINARY,
                        'type_id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1673722282, $throwable);
        }

        return new IdentityDirectionCreateResultCollection($result);
    }
}
