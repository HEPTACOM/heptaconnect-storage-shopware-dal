<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Create\RouteCreateResults;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\RouteCapabilityAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;

class RouteCreate implements RouteCreateActionInterface
{
    private Connection $connection;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityTypeAccessor $entityTypes;

    private RouteCapabilityAccessor $routeCapabilities;

    public function __construct(
        Connection $connection,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityTypeAccessor $entityTypes,
        RouteCapabilityAccessor $routeCapabilities
    ) {
        $this->connection = $connection;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityTypes = $entityTypes;
        $this->routeCapabilities = $routeCapabilities;
    }

    public function create(RouteCreatePayloads $payloads): RouteCreateResults
    {
        $capabilities = [];
        $entityTypes = [];

        /** @var RouteCreatePayload $payload */
        foreach ($payloads as $payload) {
            $sourceKey = $payload->getSourcePortalNodeKey();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573803, new UnsupportedStorageKeyException(\get_class($sourceKey)));
            }

            $targetKey = $payload->getTargetPortalNodeKey();

            if (!$targetKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573804, new UnsupportedStorageKeyException(\get_class($targetKey)));
            }

            $entityTypes[] = $payload->getEntityType();
            $capabilities[] = $payload->getCapabilities();
        }

        $allCapabilities = \array_merge([], ...$capabilities);
        $entityTypeIds = $this->entityTypes->getIdsForTypes($entityTypes, Context::createDefaultContext());
        $capabilityIds = $this->routeCapabilities->getIdsForNames($allCapabilities);

        foreach ($allCapabilities as $capability) {
            if (!\array_key_exists($capability, $capabilityIds)) {
                /** @var RouteCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if (\in_array($capability, $payload->getCapabilities(), true)) {
                        throw new InvalidCreatePayloadException($payload, 1636573805);
                    }
                }
            }
        }

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                /** @var RouteCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getEntityType() === $entityType) {
                        throw new InvalidCreatePayloadException($payload, 1636573806);
                    }
                }
            }
        }

        $keys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(RouteKeyInterface::class, $payloads->count())));
        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $routeInserts = [];
        $routeCapabilityInserts = [];
        $result = [];

        foreach ($payloads as $payload) {
            $key = $keys->current();
            $keys->next();

            if (!$key instanceof RouteStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573807, new UnsupportedStorageKeyException(\get_class($key)));
            }

            /** @var PortalNodeStorageKey $sourceKey */
            $sourceKey = $payload->getSourcePortalNodeKey();
            /** @var PortalNodeStorageKey $targetKey */
            $targetKey = $payload->getTargetPortalNodeKey();

            $routeInserts[] = [
                'id' => \hex2bin($key->getUuid()),
                'source_id' => \hex2bin($sourceKey->getUuid()),
                'target_id' => \hex2bin($targetKey->getUuid()),
                'type_id' => \hex2bin($entityTypeIds[$payload->getEntityType()]),
                'created_at' => $now,
            ];

            foreach ($payload->getCapabilities() as $capability) {
                $routeCapabilityInserts[] = [
                    'route_id' => \hex2bin($key->getUuid()),
                    'route_capability_id' => \hex2bin($capabilityIds[$capability]),
                    'created_at' => $now,
                ];
            }

            $result[] = new RouteCreateResult($key);
        }

        try {
            $this->connection->transactional(function () use ($routeCapabilityInserts, $routeInserts): void {
                // TODO batch
                foreach ($routeInserts as $routeInsert) {
                    $this->connection->insert('heptaconnect_route', $routeInsert, [
                        'id' => Types::BINARY,
                        'source_id' => Types::BINARY,
                        'target_id' => Types::BINARY,
                        'type_id' => Types::BINARY,
                    ]);
                }

                foreach ($routeCapabilityInserts as $routeCapabilityInsert) {
                    $this->connection->insert('heptaconnect_route_has_capability', $routeCapabilityInsert, [
                        'route_id' => Types::BINARY,
                        'route_capability_id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1636576240, $throwable);
        }

        return new RouteCreateResults($result);
    }
}