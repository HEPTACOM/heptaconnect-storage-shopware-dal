<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\ArrayParameterType;
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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Psr\Log\LoggerInterface;

abstract readonly class PortalExtensionSwitchActive
{
    public const string CLASS_NAME_LOOKUP_QUERY = 'a6bbbe3b-bf42-455d-824e-8c1aac4453b6';

    public const string ID_LOOKUP_QUERY = '2fc478d7-4f03-4a3d-a335-d6daf4244c27';

    public const string SWITCH_QUERY = '5444ccf3-cf11-4a5b-bf5f-8c268dce9c1a';

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory,
        private LoggerInterface $logger,
    ) {
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

        $knownExtClasses = [];
        $classNameBuilder = $this->getSelectByClassNameQueryBuilder()
            ->setParameter('portalNodeId', $portalNodeId, Types::BINARY)
            ->setParameter('extensionClassNames', $extensionsToToggle, ArrayParameterType::STRING);

        /** @var array{id: string, class_name: string, active: string} $existingExtension */
        foreach ($classNameBuilder->iterateRows('portal_node_extension.id') as $existingExtension) {
            $className = $existingExtension['class_name'];
            $knownExtClasses[] = $className;

            if (((int) $existingExtension['active']) === $this->getTargetActiveState()) {
                $pass[Id::toHex($existingExtension['id'])] = $className;
            } else {
                $updates[] = [
                    'id' => $existingExtension['id'],
                    'class_name' => $className,
                ];
            }
        }

        $missingExtensions = \array_diff($extensionsToToggle, $knownExtClasses);

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
                ->setParameter('ids', $updateIds, ArrayParameterType::STRING)
                ->setParameter('now', $now)
                ->executeStatement();

            if ($affected === \count($updates)) {
                foreach ($updates as $updatePayload) {
                    $pass[Id::toHex($updatePayload['id'])] = $updatePayload['class_name'];
                }
            } else {
                $knownExtensionBuilder = $this->getSelectByIdQueryBuilder()
                    ->setParameter('ids', $updateIds, ArrayParameterType::STRING);

                /** @var array{id: string, class_name: string, active: string} $existingExtension */
                foreach ($knownExtensionBuilder->iterateRows('portal_node_extension.id') as $existingExtension) {
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

    protected function getSelectByClassNameQueryBuilder(): SelectQueryBuilder
    {
        $result = $this->queryFactory->createSelectBuilder(self::CLASS_NAME_LOOKUP_QUERY);
        $expr = $result->expr();

        return $result
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

    protected function getSelectByIdQueryBuilder(): SelectQueryBuilder
    {
        $result = $this->queryFactory->createSelectBuilder(self::ID_LOOKUP_QUERY);
        $expr = $result->expr();

        return $result
            ->select([
                'portal_node_extension.id',
                'portal_node_extension.class_name',
                'portal_node_extension.active',
            ])
            ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
            ->where($expr->in('portal_node_extension.id', ':ids'));
    }

    protected function getUpdateQueryBuilder(): QueryBuilder
    {
        $result = $this->queryFactory->createBuilder(self::SWITCH_QUERY);
        $expr = $result->expr();

        return $result
            ->update('heptaconnect_portal_node_extension', 'portal_node_extension')
            ->set('portal_node_extension.active', (string) $this->getTargetActiveState())
            ->set('portal_node_extension.updated_at', ':now')
            ->where($expr->in('portal_node_extension.id', ':ids'));
    }

    protected function getPortalNodeId(PortalNodeKeyInterface $portalNodeKey): string
    {
        $portalNodeKey = $portalNodeKey->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($portalNodeKey);
        }

        return Id::toBinary($portalNodeKey->getUuid());
    }
}
