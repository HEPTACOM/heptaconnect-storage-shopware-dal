<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Set\PortalNodeConfigurationSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class PortalNodeConfigurationSet implements PortalNodeConfigurationSetActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function set(PortalNodeConfigurationSetPayloads $payloads): void
    {
        $updates = [];

        /** @var PortalNodeConfigurationSetPayload $payload */
        foreach ($payloads as $payload) {
            $portalNodeKey = $payload->getPortalNodeKey();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1642863637, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
            }

            $jsonValue = '{}';
            $configuration = $payload->getValue();

            if (\is_array($configuration)) {
                try {
                    $jsonValue = \json_encode($configuration, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    throw new InvalidCreatePayloadException($payload, 1642863638, $e);
                }
            }

            $updates[$portalNodeKey->getUuid()] = $jsonValue;
        }

        if ($updates === []) {
            return;
        }

        try {
            $this->connection->transactional(function () use ($updates): void {
                // TODO batch
                foreach ($updates as $portalNodeId => $configuration) {
                    $this->connection->update('heptaconnect_portal_node', [
                        'configuration' => $configuration,
                    ], [
                        'id' => \hex2bin($portalNodeId),
                    ], [
                        'id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1642863639, $throwable);
        }
    }
}
