<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Set\PortalNodeAliasSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Set\PortalNodeAliasSetPayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\EmptyAliasStringException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidAliasSetPayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UpdateException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class PortalNodeAliasSet implements PortalNodeAliasSetActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function set(PortalNodeAliasSetPayloads $payloads): void
    {
        $updates = [];
        /** @var PortalNodeAliasSetPayload $payload */
        foreach ($payloads as $payload) {
            $portalNodeKey = $payload->getPortalNodeKey();
            $alias = $payload->getAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new InvalidAliasSetPayloadException($payload, 1645446078, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
            }
            if ($alias === '') {
                throw new InvalidAliasSetPayloadException($payload, 1645446809, new EmptyAliasStringException());
            }
            $updates[$portalNodeKey->getUuid()] = $alias;
        }

        if ($updates === []) {
            return;
        }

        try {
            $this->connection->transactional(function () use ($updates): void {
                // TODO batch
                foreach ($updates as $portalNodeId => $alias) {
                    $this->connection->update('heptaconnect_portal_node', [
                        'alias' => $alias,
                    ], [
                        'id' => \hex2bin($portalNodeId),
                    ], [
                        'id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new UpdateException(1645448849, $throwable);
        }
    }
}
