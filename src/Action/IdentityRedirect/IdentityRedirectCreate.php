<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityRedirect;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Create\IdentityRedirectCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Create\IdentityRedirectCreatePayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Create\IdentityRedirectCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Create\IdentityRedirectCreateResultCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityRedirect\IdentityRedirectCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\IdentityRedirectKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityRedirectStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class IdentityRedirectCreate implements IdentityRedirectCreateActionInterface
{
    public function __construct(
        private Connection $connection,
        private StorageKeyGeneratorContract $storageKeyGenerator,
        private EntityTypeAccessor $entityTypes
    ) {
    }

    public function create(IdentityRedirectCreatePayloadCollection $payloads): IdentityRedirectCreateResultCollection
    {
        $entityTypes = [];

        /** @var IdentityRedirectCreatePayload $payload */
        foreach ($payloads as $payload) {
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722278, new UnsupportedStorageKeyException(\get_class($sourceKey)));
            }

            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            if (!$targetKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722279, new UnsupportedStorageKeyException(\get_class($targetKey)));
            }

            $entityTypes[] = (string) $payload->getEntityType();
        }

        $entityTypeIds = $this->entityTypes->getIdsForTypes($entityTypes);

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                /** @var IdentityRedirectCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if (((string) $payload->getEntityType()) === $entityType) {
                        throw new InvalidCreatePayloadException($payload, 1673722280);
                    }
                }
            }
        }

        $keys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(IdentityRedirectKeyInterface::class, $payloads->count())));
        $now = DateTime::nowToStorage();
        $identityRedirectInserts = [];
        $result = [];

        foreach ($payloads as $payload) {
            $key = $keys->current();
            $keys->next();

            if (!$key instanceof IdentityRedirectStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1673722281, new UnsupportedStorageKeyException(\get_class($key)));
            }

            /** @var PortalNodeStorageKey $sourceKey */
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();
            /** @var PortalNodeStorageKey $targetKey */
            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            $identityRedirectInserts[] = [
                'id' => Id::toBinary($key->getUuid()),
                'source_portal_node_id' => Id::toBinary($sourceKey->getUuid()),
                'source_external_id' => $payload->getSourceExternalId(),
                'target_portal_node_id' => Id::toBinary($targetKey->getUuid()),
                'target_external_id' => $payload->getTargetExternalId(),
                'type_id' => Id::toBinary($entityTypeIds[(string) $payload->getEntityType()]),
                'created_at' => $now,
            ];

            $result[] = new IdentityRedirectCreateResult($key);
        }

        try {
            $this->connection->transactional(function () use ($identityRedirectInserts): void {
                // TODO batch
                foreach ($identityRedirectInserts as $identityRedirectInsert) {
                    $this->connection->insert('heptaconnect_identity_redirect', $identityRedirectInsert, [
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

        return new IdentityRedirectCreateResultCollection($result);
    }
}
