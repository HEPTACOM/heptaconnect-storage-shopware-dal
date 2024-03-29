<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor;

final class WebHttpHandlerConfigurationSet implements WebHttpHandlerConfigurationSetActionInterface
{
    public function __construct(
        private Connection $connection,
        private WebHttpHandlerPathAccessor $webHttpHandlerPathAccessor,
        private WebHttpHandlerAccessor $webHttpHandlerAccessor
    ) {
    }

    public function set(WebHttpHandlerConfigurationSetPayloads $payloads): void
    {
        $handlerPaths = [];
        $handlerComponents = [];

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayload $payload */
        foreach ($payloads as $payload) {
            $portalNodeKey = $payload->getStackIdentifier()->getPortalNodeKey()->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636827821, new UnsupportedStorageKeyException($portalNodeKey::class));
            }

            $path = $payload->getStackIdentifier()->getPath();
            $handlerPaths[] = $path;
            $handlerComponents[$portalNodeKey->getUuid() . $path] = [$portalNodeKey, $path];
        }

        $handlerPathIds = $this->webHttpHandlerPathAccessor->getIdsForPaths($handlerPaths);

        foreach ($handlerPaths as $handlerPath) {
            if (!isset($handlerPathIds[$handlerPath])) {
                /** @var \Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getStackIdentifier()->getPath() === $handlerPath) {
                        throw new InvalidCreatePayloadException($payload, 1636827822);
                    }
                }
            }
        }

        $handlerComponentIds = $this->webHttpHandlerAccessor->getIdsForHandlers($handlerComponents);

        foreach ($handlerComponents as $handlerComponentKey => $handlerComponent) {
            if (!isset($handlerComponentIds[$handlerComponentKey])) {
                [$portalNodeKey, $path] = $handlerComponent;

                /** @var \Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getStackIdentifier()->getPath() === $path && $payload->getStackIdentifier()->getPortalNodeKey()->equals($portalNodeKey)) {
                        throw new InvalidCreatePayloadException($payload, 1636827823);
                    }
                }
            }
        }

        $now = DateTime::nowToStorage();
        $deletes = [];
        $upserts = [];

        foreach ($payloads as $payload) {
            /** @var PortalNodeStorageKey $portalNodeKey */
            $portalNodeKey = $payload->getStackIdentifier()->getPortalNodeKey()->withoutAlias();
            $pathId = $handlerPathIds[$payload->getStackIdentifier()->getPath()];
            $handlerId = $handlerComponentIds[$portalNodeKey->getUuid() . $payload->getStackIdentifier()->getPath()];

            if ($payload->getConfigurationValue() === null) {
                $deletes[] = [
                    'handler_id' => Id::toBinary($handlerId),
                    '`key`' => $payload->getConfigurationKey(),
                ];

                continue;
            }

            $upserts[] = [
                'id' => Id::randomBinary(),
                'handler_id' => Id::toBinary($handlerId),
                '`key`' => $payload->getConfigurationKey(),
                'value' => \serialize($payload->getConfigurationValue()),
                'type' => 'serialized',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        try {
            $this->connection->transactional(function () use ($upserts, $deletes): void {
                // TODO batch
                foreach ($deletes as $delete) {
                    $this->connection->delete('heptaconnect_web_http_handler_configuration', $delete, [
                        'handler_id' => Types::BINARY,
                    ]);
                }

                foreach ($upserts as $upsert) {
                    $where = $upsert;
                    $update = $upsert;
                    unset(
                        $where['id'], $where['value'], $where['type'], $where['created_at'], $where['updated_at'],
                        $update['id'], $update['handler_id'], $update['`key`'], $update['created_at'],
                    );

                    $updated = $this->connection->update('heptaconnect_web_http_handler_configuration', $upsert, $where, [
                        'handler_id' => Types::BINARY,
                    ]);

                    if ($updated === 0) {
                        unset($upsert['updated_at']);
                        $this->connection->insert('heptaconnect_web_http_handler_configuration', $upsert, [
                            'handler_id' => Types::BINARY,
                        ]);
                    }
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1636827824, $throwable);
        }
    }
}
