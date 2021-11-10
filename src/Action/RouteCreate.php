<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreateResults;
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
            $sourceKey = $payload->getSource();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573803, new UnsupportedStorageKeyException(\get_class($sourceKey)));
            }

            $targetKey = $payload->getTarget();

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
                throw new InvalidCreatePayloadException($payload, 1636573805);
            }
        }

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                throw new InvalidCreatePayloadException($payload, 1636573806);
            }
        }

        $keys = \iterable_to_traversable($this->storageKeyGenerator->generateKeys(RouteKeyInterface::class, $payloads->count()));
        $now = \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $routeInserts = [];
        $routeCapabilityInserts = [];
        $result = [];

        foreach ($payloads as $payload) {
            $key = $keys->current();

            if (!$key instanceof RouteStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573807, new UnsupportedStorageKeyException(\get_class($key)));
            }

            /** @var PortalNodeStorageKey $sourceKey */
            $sourceKey = $payload->getSource();
            /** @var PortalNodeStorageKey $targetKey */
            $targetKey = $payload->getTarget();

            $routeInserts[] = [
                'id' => \hex2bin($key->getUuid()),
                'source_id' => \hex2bin($sourceKey->getUuid()),
                'target_id' => \hex2bin($targetKey->getUuid()),
                'type_id' => \hex2bin($entityTypeIds[$payload->getEntityType()]),
                'created_at' => $now,
            ];

            foreach ($payload->getCapabilities() as $capability) {
                $routeCapabilityInserts[] = [
                    'route_id' => \bin2hex($key->getUuid()),
                    'route_capability_id' => \bin2hex($capabilityIds[$capability]),
                    'created_at' => $now,
                ];
            }

            $result[] = new RouteCreateResult($key);
        }

        try {
            $this->connection->transactional(function () use ($routeCapabilityInserts, $routeInserts) {
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
