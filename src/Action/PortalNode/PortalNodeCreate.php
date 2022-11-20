<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreateResults;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalNodeAliasAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class PortalNodeCreate implements PortalNodeCreateActionInterface
{
    public function __construct(private Connection $connection, private StorageKeyGeneratorContract $storageKeyGenerator, private PortalNodeAliasAccessor $portalNodeAliasAccessor)
    {
    }

    public function create(PortalNodeCreatePayloads $payloads): PortalNodeCreateResults
    {
        $keys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(PortalNodeKeyInterface::class, $payloads->count())));
        $now = DateTime::nowToStorage();
        $inserts = [];
        $result = [];

        /** @var \Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload $payload */
        foreach ($payloads as $payload) {
            $key = $keys->current();
            $keys->next();

            if (!$key instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1640048751, new UnsupportedStorageKeyException($key::class));
            }

            $alias = $payload->getAlias();

            if ($alias === '') {
                throw new InvalidCreatePayloadException($payload, 1648345724);
            }

            if ($alias !== null) {
                if ($this->portalNodeAliasAccessor->getIdsByAliases([$alias]) !== []) {
                    throw new InvalidCreatePayloadException($payload, 1648345725);
                }
            }

            $inserts[] = [
                'id' => Id::toBinary($key->getUuid()),
                'alias' => $alias,
                'class_name' => (string) $payload->getPortalClass(),
                'configuration' => '{}',
                'created_at' => $now,
            ];
            $result[] = new PortalNodeCreateResult($key);
        }

        try {
            $this->connection->transactional(function () use ($inserts): void {
                // TODO batch
                foreach ($inserts as $insert) {
                    $this->connection->insert('heptaconnect_portal_node', $insert, [
                        'id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1640048752, $throwable);
        }

        return new PortalNodeCreateResults($result);
    }
}
