<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Set\WebHttpHandlerConfigurationSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathAccessor;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class WebHttpHandlerConfigurationSet implements WebHttpHandlerConfigurationSetActionInterface
{
    private Connection $connection;

    private WebHttpHandlerPathAccessor $webHttpHandlerPathAccessor;

    private WebHttpHandlerAccessor $webHttpHandlerAccessor;

    public function __construct(
        Connection $connection,
        WebHttpHandlerPathAccessor $webHttpHandlerPathAccessor,
        WebHttpHandlerAccessor $webHttpHandlerAccessor
    ) {
        $this->connection = $connection;
        $this->webHttpHandlerPathAccessor = $webHttpHandlerPathAccessor;
        $this->webHttpHandlerAccessor = $webHttpHandlerAccessor;
    }

    public function set(WebHttpHandlerConfigurationSetPayloads $payloads): void
    {
        $handlerPaths = [];
        $handlerComponents = [];

        /** @var WebHttpHandlerConfigurationSetPayload $payload */
        foreach ($payloads as $payload) {
            $portalNodeKey = $payload->getPortalNodeKey();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1636827821, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
            }

            $handlerPaths[] = $payload->getPath();
            $handlerComponents[$portalNodeKey->getUuid() . $payload->getPath()] = [$portalNodeKey, $payload->getPath()];
        }

        $handlerPathIds = $this->webHttpHandlerPathAccessor->getIdsForPaths($handlerPaths);

        foreach ($handlerPaths as $handlerPath) {
            if (!isset($handlerPathIds[$handlerPath])) {
                /** @var WebHttpHandlerConfigurationSetPayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getPath() === $handlerPath) {
                        throw new InvalidCreatePayloadException($payload, 1636827822);
                    }
                }
            }
        }

        $handlerComponentIds = $this->webHttpHandlerAccessor->getIdsForHandlers($handlerComponents);

        foreach ($handlerComponents as $handlerComponentKey => $handlerComponent) {
            if (!isset($handlerComponentIds[$handlerComponentKey])) {
                [$portalNodeKey, $path] = $handlerComponent;

                /** @var WebHttpHandlerConfigurationSetPayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getPath() === $path && $payload->getPortalNodeKey()->equals($portalNodeKey)) {
                        throw new InvalidCreatePayloadException($payload, 1636827823);
                    }
                }
            }
        }

        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        $deletes = [];
        $upserts = [];

        foreach ($payloads as $payload) {
            /** @var PortalNodeStorageKey $portalNodeKey */
            $portalNodeKey = $payload->getPortalNodeKey();
            $pathId = $handlerPathIds[$payload->getPath()];
            $handlerId = $handlerComponentIds[$portalNodeKey->getUuid() . $payload->getPath()];

            if ($payload->getConfigurationValue() === null) {
                $deletes[] = [
                    'handler_id' => \hex2bin($handlerId),
                    'key' => $payload->getConfigurationKey(),
                ];

                continue;
            }

            $upserts[] = [
                'id' => Uuid::randomBytes(),
                'handler_id' => \hex2bin($handlerId),
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
                        'handler_id' => Type::BINARY,
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
                        'handler_id' => Type::BINARY,
                    ]);

                    if ($updated === 0) {
                        unset($upsert['updated_at']);
                        $this->connection->insert('heptaconnect_web_http_handler_configuration', $upsert, [
                            'handler_id' => Type::BINARY,
                        ]);
                    }
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1636827824, $throwable);
        }
    }
}
