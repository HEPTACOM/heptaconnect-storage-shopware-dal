<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeStorage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeStorage\Set\PortalNodeStorageSetPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeStorage\PortalNodeStorageSetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeStorageSet implements PortalNodeStorageSetActionInterface
{
    public const UPDATE_PREPARATION_QUERY = '75fada39-34f0-4e03-b3b5-141da358181d';

    public function __construct(private QueryFactory $queryFactory, private Connection $connection)
    {
    }

    public function set(PortalNodeStorageSetPayload $payload): void
    {
        $portalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($portalNodeKey::class);
        }

        $keysToCheck = [];
        $instructions = [];
        $now = new \DateTimeImmutable();
        $nowFormatted = DateTime::toStorage($now);

        foreach ($payload->getSets() as $set) {
            $expiresIn = $set->getExpiresIn();
            $expiresAt = $expiresIn instanceof \DateInterval ? $now->add($expiresIn) : null;

            $storageKey = $set->getStorageKey();
            $keysToCheck[] = $storageKey;
            $instructions[$storageKey] = [
                '`key`' => $set->getStorageKey(),
                'value' => $set->getValue(),
                'type' => $set->getType(),
                'portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
                'created_at' => $nowFormatted,
                'updated_at' => $nowFormatted,
                'expired_at' => $expiresAt instanceof \DateTimeInterface ? DateTime::toStorage($expiresAt) : null,
            ];
        }

        $fetchBuilder = $this->queryFactory->createBuilder(self::UPDATE_PREPARATION_QUERY);
        $fetchBuilder
            ->from('heptaconnect_portal_node_storage', 'portal_node_storage')
            ->select([
                'portal_node_storage.key storage_key',
            ])
            ->innerJoin(
                'portal_node_storage',
                'heptaconnect_portal_node',
                'portal_node',
                $fetchBuilder->expr()->eq('portal_node_storage.portal_node_id', 'portal_node.id')
            )
            ->addOrderBy('portal_node_storage.id')
            ->andWhere($fetchBuilder->expr()->in('portal_node_storage.key', ':ids'))
            ->andWhere($fetchBuilder->expr()->eq('portal_node.id', ':portal_node_id'))
            ->andWhere($fetchBuilder->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($fetchBuilder->expr()->or(
                $fetchBuilder->expr()->isNull('expired_at'),
                $fetchBuilder->expr()->gt('expired_at', ':now')
            ))
            ->setParameter('ids', $keysToCheck, Connection::PARAM_STR_ARRAY)
            ->setParameter('portal_node_id', Id::toBinary($portalNodeKey->getUuid()), Types::BINARY)
            ->setParameter('now', $nowFormatted);

        try {
            $this->connection->transactional(function () use ($instructions, $keysToCheck, $fetchBuilder): void {
                $keysToUpdate = \array_fill_keys($keysToCheck, false);

                $fetchBuilder->setIsForUpdate(true);

                foreach ($fetchBuilder->iterateColumn() as $storageKey) {
                    $keysToUpdate[$storageKey] = true;
                }

                // TODO batch
                foreach ($instructions as $storageKey => $instruction) {
                    if ($keysToUpdate[$storageKey] ?? false) {
                        $condition = [
                            'portal_node_id' => $instruction['portal_node_id'],
                            '`key`' => $instruction['`key`'],
                        ];
                        unset($instruction['portal_node_id'], $instruction['`key`'], $instruction['created_at']);

                        $this->connection->update('heptaconnect_portal_node_storage', $instruction, $condition, [
                            'portal_node_id' => Types::BINARY,
                        ]);
                    } else {
                        unset($instruction['updated_at']);
                        $instruction['id'] = Id::randomBinary();

                        $this->connection->insert('heptaconnect_portal_node_storage', $instruction, [
                            'id' => Types::BINARY,
                            'portal_node_id' => Types::BINARY,
                        ]);
                    }
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1646341933, $throwable);
        }
    }
}
