<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalExtensionContract;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

abstract class PortalExtensionSwitchActive implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private Connection $connection;

    private ?QueryBuilder $selectByClassNameQueryBuilder = null;

    private ?QueryBuilder $selectByIdQueryBuilder = null;

    private ?QueryBuilder $updateQueryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->setLogger(new NullLogger());
    }

    abstract protected function getTargetActiveState(): int;

    /**
     * @param array<class-string<PortalExtensionContract>> $payloadExtensions
     *
     * @return array<class-string<PortalExtensionContract>>
     */
    protected function toggle(PortalNodeKeyInterface $portalNodeKey, array $payloadExtensions)
    {
        $portalNodeId = $this->getPortalNodeId($portalNodeKey);
        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $pass = $updates = [];

        $existingExtensions = $this->getSelectByClassNameQueryBuilder()
            ->setParameter('portalNodeId', $portalNodeId, Types::BINARY)
            ->setParameter('extensionClassNames', $payloadExtensions, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll(FetchMode::ASSOCIATIVE) ?: [];

        foreach ($existingExtensions as $existingExtension) {
            if (((int) $existingExtension['active']) === $this->getTargetActiveState()) {
                $pass[\bin2hex($existingExtension['id'])] = $existingExtension['class_name'];
            } else {
                $updates[] = [
                    'id' => $existingExtension['id'],
                    'class_name' => $existingExtension['class_name'],
                ];
            }
        }

        $missingExtensions = \array_diff(
            $payloadExtensions,
            \array_column($existingExtensions, 'class_name')
        );

        foreach ($missingExtensions as $missingExtension) {
            $missingExtensionId = Uuid::uuid4();

            try {
                $affected = $this->connection->insert('heptaconnect_portal_node_extension', [
                    'id' => $missingExtensionId->getBytes(),
                    'portal_node_id' => $portalNodeId,
                    'class_name' => $missingExtension,
                    'active' => $this->getTargetActiveState(),
                    'created_at' => $now,
                ], [
                    'id' => Types::BINARY,
                    'portal_node_id' => Types::BINARY,
                ]);
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());

                continue;
            }

            if ($affected === 1) {
                $pass[(string) $missingExtensionId->getHex()] = $missingExtension;
            }
        }

        if ($updates !== []) {
            $updateIds = \array_column($updates, 'id');

            $affected = $this->getUpdateQueryBuilder()
                ->setParameter('ids', $updateIds, Connection::PARAM_STR_ARRAY)
                ->setParameter('now', $now)
                ->execute();

            if ($affected === \count($updates)) {
                foreach ($updates as $updatePayload) {
                    $pass[\bin2hex($updatePayload['id'])] = $updatePayload['class_name'];
                }
            } else {
                $existingExtensions = $this->getSelectByIdQueryBuilder()
                    ->setParameter('ids', $updateIds, Connection::PARAM_STR_ARRAY)
                    ->execute()
                    ->fetchAll(FetchMode::ASSOCIATIVE) ?: [];

                foreach ($existingExtensions as $existingExtension) {
                    if (((int) $existingExtension['active']) === $this->getTargetActiveState()) {
                        $pass[\bin2hex($existingExtension['id'])] = $existingExtension['class_name'];
                    }
                }
            }
        }

        return \array_values($pass);
    }

    protected function getSelectByClassNameQueryBuilder(): QueryBuilder
    {
        if (!$this->selectByClassNameQueryBuilder instanceof QueryBuilder) {
            $this->selectByClassNameQueryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->selectByClassNameQueryBuilder->expr();

            $this->selectByClassNameQueryBuilder
                ->select([
                    'portal_node_extension.id',
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->where(
                    $expr->eq('portal_node_extension.portal_node_id', ':portalNodeId'),
                    $expr->in('portal_node_extension.class_name', ':extensionClassNames')
                );
        }

        return $this->selectByClassNameQueryBuilder;
    }

    protected function getSelectByIdQueryBuilder(): QueryBuilder
    {
        if (!$this->selectByIdQueryBuilder instanceof QueryBuilder) {
            $this->selectByIdQueryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->selectByIdQueryBuilder->expr();

            $this->selectByIdQueryBuilder
                ->select([
                    'portal_node_extension.id',
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->where($expr->in('portal_node_extension.id', ':ids'));
        }

        return $this->selectByIdQueryBuilder;
    }

    protected function getUpdateQueryBuilder(): QueryBuilder
    {
        if (!$this->updateQueryBuilder instanceof QueryBuilder) {
            $this->updateQueryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->updateQueryBuilder->expr();

            $this->updateQueryBuilder
                ->update('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->set('portal_node_extension.active', $this->getTargetActiveState())
                ->set('portal_node_extension.updated_at', ':now')
                ->where($expr->in('portal_node_extension.id', ':ids'));
        }

        return $this->updateQueryBuilder;
    }

    protected function getPortalNodeId(PortalNodeKeyInterface $portalNodeKey): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        return \hex2bin($portalNodeKey->getUuid());
    }
}