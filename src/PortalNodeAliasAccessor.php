<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class PortalNodeAliasAccessor
{
    public const ID_LOOKUP_QUERY = '8f493191-2ba8-4c9f-b4ff-641fc1afdc56';

    public const ALIAS_LOOKUP_QUERY = '81bd204c-97c0-4259-bf82-8b835f2f0237';

    private array $known = [];

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    /**
     * @psalm-param array<array-key, string> $ids
     * @psalm-return array<string, string>
     */
    public function getAliasesByIds(array $ids): array
    {
        $ids = \array_keys(\array_flip($ids));
        $knownIds = \array_keys($this->known);
        $nonMatchingIds = \array_diff($ids, $knownIds);

        if ($nonMatchingIds !== []) {
            $builder = $this->queryFactory->createBuilder(self::ID_LOOKUP_QUERY);
            $builder
                ->from('heptaconnect_portal_node', 'portal_node')
                ->select([
                    'portal_node.id id',
                    'portal_node.alias alias',
                ])
                ->addOrderBy('portal_node.id')
                ->andWhere($builder->expr()->in('portal_node.id', ':ids'))
                ->andWhere($builder->expr()->isNotNull('portal_node.alias'))
                ->setParameter('ids', $nonMatchingIds, Connection::PARAM_STR_ARRAY);

            $aliasedIds = [];

            foreach ($builder->iterateRows() as $row) {
                $aliasedIds[Id::toHex($row['id'])] = $row['alias'];
            }

            $this->known = \array_merge($this->known, $aliasedIds);
        }

        return \array_intersect_key($this->known, \array_fill_keys($ids, true));
    }

    /**
     * @psalm-param array<array-key, string> $aliases
     * @psalm-return array<string, string>
     */
    public function getIdsByAliases(array $aliases): array
    {
        $aliases = \array_keys(\array_flip($aliases));
        $knownAliases = \array_flip($this->known);
        $nonMatchingAliases = \array_diff($aliases, $knownAliases);

        if ($nonMatchingAliases !== []) {
            $builder = $this->queryFactory->createBuilder(self::ALIAS_LOOKUP_QUERY);
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
                ->setParameter('aliases', $nonMatchingAliases, Connection::PARAM_STR_ARRAY);

            $aliasedIds = [];

            foreach ($builder->iterateRows() as $row) {
                $aliasedIds[Id::toHex($row['id'])] = $row['alias'];
            }

            $this->known = \array_merge($this->known, $aliasedIds);
        }

        return \array_intersect_key(\array_flip($this->known), \array_fill_keys($aliases, true));
    }
}
