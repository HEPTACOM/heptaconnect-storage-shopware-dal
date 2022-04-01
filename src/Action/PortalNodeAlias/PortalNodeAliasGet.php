<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Get\PortalNodeAliasGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Get\PortalNodeAliasGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeAliasGet implements PortalNodeAliasGetActionInterface
{
    public const FETCH_QUERY = 'f3e31372-bc6b-444d-99ee-38b74f9cf9fc';

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function get(PortalNodeAliasGetCriteria $criteria): iterable
    {
        $portalNodeIds = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeKey = $portalNodeKey->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $portalNodeIds[] = $portalNodeKey->getUuid();
        }

        if ($portalNodeIds === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);
        $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.alias alias',
            ])
            ->addOrderBy('portal_node.id')
            ->andWhere($builder->expr()->in('portal_node.id', ':ids'))
            ->andWhere($builder->expr()->isNotNull('portal_node.alias'))
            ->andWhere($builder->expr()->isNull('portal_node.deleted_at'))
            ->setParameter('ids', Id::toBinaryList($portalNodeIds), Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $builder->iterateRows(),
            static fn (array $row): PortalNodeAliasGetResult => new PortalNodeAliasGetResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['id'])),
                (string) $row['alias']
            )
        );
    }
}
