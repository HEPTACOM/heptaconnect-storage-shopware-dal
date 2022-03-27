<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasFindActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeAliasFind implements PortalNodeAliasFindActionInterface
{
    public const FIND_QUERY = '8ffc1022-c03b-4f3f-a2f6-5807710dbb6f';

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function find(PortalNodeAliasFindCriteria $criteria): iterable
    {
        $aliases = \array_values($criteria->getAlias());

        if ($aliases === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::FIND_QUERY);
        $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.alias alias',
            ])
            ->addOrderBy('portal_node.id')
            ->andWhere($builder->expr()->in('portal_node.alias', ':aliases'))
            ->andWhere($builder->expr()->isNotNull('portal_node.alias'))
            ->andWhere($builder->expr()->isNull('portal_node.deleted_at'))
            ->setParameter('aliases', $aliases, Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $builder->iterateRows(),
            static fn (array $row): PortalNodeAliasFindResult => new PortalNodeAliasFindResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['id'])),
                (string) $row['alias']
            )
        );
    }
}
