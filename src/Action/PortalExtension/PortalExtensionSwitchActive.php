<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionType;
use Heptacom\HeptaConnect\Portal\Base\Portal\PortalExtensionTypeCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

abstract class PortalExtensionSwitchActive implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CLASS_NAME_LOOKUP_QUERY = 'a6bbbe3b-bf42-455d-824e-8c1aac4453b6';

    public const ID_LOOKUP_QUERY = '2fc478d7-4f03-4a3d-a335-d6daf4244c27';

    public const SWITCH_QUERY = '5444ccf3-cf11-4a5b-bf5f-8c268dce9c1a';

    private ?QueryBuilder $selectByClassNameQueryBuilder = null;

    private ?QueryBuilder $selectByIdQueryBuilder = null;

    private ?QueryBuilder $updateQueryBuilder = null;

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory
    ) {
        $this->setLogger(new NullLogger());
    }

    abstract protected function getTargetActiveState(): int;

    protected function toggle(
        PortalNodeKeyInterface $portalNodeKey,
        PortalExtensionTypeCollection $payloadExtensions
    ): PortalExtensionTypeCollection {
        $extensionsToToggle = \iterable_to_array($payloadExtensions->map(
            static fn (PortalExtensionType $type): string => (string) $type
        ));

        $portalNodeId = $this->getPortalNodeId($portalNodeKey);
        $now = DateTime::nowToStorage();

        $pass = $updates = [];

        $existingExtensions = [];
        $existingExtensionRows = $this->getSelectByClassNameQueryBuilder()
            ->setParameter('portalNodeId', $portalNodeId, Types::BINARY)
            ->setParameter('extensionClassNames', $extensionsToToggle, Connection::PARAM_STR_ARRAY)
            ->iterateRows();

        foreach ($existingExtensionRows as $existingExtension) {
            $className = $existingExtension['class_name'];
            $existingExtensions[] = $className;

            if (((int) $existingExtension['active']) === $this->getTargetActiveState()) {
                $pass[Id::toHex($existingExtension['id'])] = $className;
            } else {
                $updates[] = [
                    'id' => $existingExtension['id'],
                    'class_name' => $className,
                ];
            }
        }

        $missingExtensions = \array_diff($extensionsToToggle, $existingExtensions);

        foreach ($missingExtensions as $missingExtension) {
            $missingExtensionId = Id::randomHex();

            try {
                $affected = $this->connection->insert('heptaconnect_portal_node_extension', [
                    'id' => Id::toBinary($missingExtensionId),
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
                $pass[$missingExtensionId] = $missingExtension;
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
                    $pass[Id::toHex($updatePayload['id'])] = $updatePayload['class_name'];
                }
            } else {
                $existingExtensions = $this->getSelectByIdQueryBuilder()
                    ->setParameter('ids', $updateIds, Connection::PARAM_STR_ARRAY)
                    ->iterateRows();

                foreach ($existingExtensions as $existingExtension) {
                    if (((int) $existingExtension['active']) === $this->getTargetActiveState()) {
                        $pass[Id::toHex($existingExtension['id'])] = $existingExtension['class_name'];
                    }
                }
            }
        }

        return new PortalExtensionTypeCollection(\array_map(
            static fn (string $passedExt): PortalExtensionType => new PortalExtensionType($passedExt),
            \array_values($pass)
        ));
    }

    protected function getSelectByClassNameQueryBuilder(): QueryBuilder
    {
        if (!$this->selectByClassNameQueryBuilder instanceof QueryBuilder) {
            $this->selectByClassNameQueryBuilder = $this->queryFactory->createBuilder(self::CLASS_NAME_LOOKUP_QUERY);
            $expr = $this->selectByClassNameQueryBuilder->expr();

            $this->selectByClassNameQueryBuilder
                ->select([
                    'portal_node_extension.id',
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->addOrderBy('portal_node_extension.id')
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
            $this->selectByIdQueryBuilder = $this->queryFactory->createBuilder(self::ID_LOOKUP_QUERY);
            $expr = $this->selectByIdQueryBuilder->expr();

            $this->selectByIdQueryBuilder
                ->select([
                    'portal_node_extension.id',
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->addOrderBy('portal_node_extension.id')
                ->where($expr->in('portal_node_extension.id', ':ids'));
        }

        return $this->selectByIdQueryBuilder;
    }

    protected function getUpdateQueryBuilder(): QueryBuilder
    {
        if (!$this->updateQueryBuilder instanceof QueryBuilder) {
            $this->updateQueryBuilder = $this->queryFactory->createBuilder(self::SWITCH_QUERY);
            $expr = $this->updateQueryBuilder->expr();

            $this->updateQueryBuilder
                ->update('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->set('portal_node_extension.active', (string) $this->getTargetActiveState())
                ->set('portal_node_extension.updated_at', ':now')
                ->where($expr->in('portal_node_extension.id', ':ids'));
        }

        return $this->updateQueryBuilder;
    }

    protected function getPortalNodeId(PortalNodeKeyInterface $portalNodeKey): string
    {
        $portalNodeKey = $portalNodeKey->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($portalNodeKey::class);
        }

        return Id::toBinary($portalNodeKey->getUuid());
    }
}
