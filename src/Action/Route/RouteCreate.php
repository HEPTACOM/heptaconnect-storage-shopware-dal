<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreateResults;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final readonly class RouteCreate implements RouteCreateActionInterface
{
    public function __construct(
        private Connection $connection,
        private EntityTypeAccessor $entityTypes,
    ) {
    }

    #[\Override]
    public function create(RouteCreatePayloads $payloads): RouteCreateResults
    {
        $entityTypes = [];

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload $payload */
        foreach ($payloads as $payload) {
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573803, new UnsupportedStorageKeyException($sourceKey));
            }

            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            if (!$targetKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636573804, new UnsupportedStorageKeyException($targetKey));
            }

            $entityTypes[] = (string) $payload->getEntityType();
        }

        $entityTypeIds = $this->entityTypes->getIdsForTypes($entityTypes);

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Route\Create\RouteCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if (((string) $payload->getEntityType()) === $entityType) {
                        throw new InvalidCreatePayloadException($payload, 1636573806);
                    }
                }
            }
        }

        $now = DateTime::nowToStorage();
        $routeInserts = [];
        $routeConfigurationInserts = [];
        $result = [];

        foreach ($payloads as $payload) {
            $id = Id::randomBinary();
            /** @var PortalNodeStorageKey $sourceKey */
            $sourceKey = $payload->getSourcePortalNodeKey()->withoutAlias();
            /** @var PortalNodeStorageKey $targetKey */
            $targetKey = $payload->getTargetPortalNodeKey()->withoutAlias();

            $routeInserts[] = [
                'id' => $id,
                'source_id' => Id::toBinary($sourceKey->getUuid()),
                'target_id' => Id::toBinary($targetKey->getUuid()),
                'type_id' => Id::toBinary($entityTypeIds[(string) $payload->getEntityType()]),
                'created_at' => $now,
            ];

            foreach ($payload->getCapabilities() as $capability) {
                $routeConfigurationInserts[] = [
                    'id' => Id::randomBinary(),
                    'route_id' => $id,
                    '`key`' => $capability,
                    'value' => 'true',
                    'type' => 'bool',
                    'created_at' => $now,
                ];
            }

            $result[] = new RouteCreateResult(new RouteStorageKey(Id::toHex($id)));
        }

        try {
            $this->connection->transactional(function () use ($routeConfigurationInserts, $routeInserts): void {
                // TODO batch
                foreach ($routeInserts as $routeInsert) {
                    $this->connection->insert('heptaconnect_route', $routeInsert, [
                        'id' => Types::BINARY,
                        'source_id' => Types::BINARY,
                        'target_id' => Types::BINARY,
                        'type_id' => Types::BINARY,
                    ]);
                }

                foreach ($routeConfigurationInserts as $routeCapabilityInsert) {
                    $this->connection->delete('heptaconnect_route_configuration', [
                        'route_id' => $routeCapabilityInsert['route_id'],
                        '`key`' => $routeCapabilityInsert['key'],
                    ], [
                        'route_id' => Types::BINARY,
                    ]);
                    $this->connection->insert('heptaconnect_route_configuration', $routeCapabilityInsert, [
                        'id' => Types::BINARY,
                        'route_id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1636576240, $throwable);
        }

        return new RouteCreateResults($result);
    }
}
