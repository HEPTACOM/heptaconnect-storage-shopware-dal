<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Identity;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Identity\IdentityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityOverview implements IdentityOverviewActionInterface
{
    public const OVERVIEW_QUERY = '510bb5ac-4bcb-4ddf-927c-05971298bc55';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function overview(IdentityOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $mappingNodeKeyFilter = $criteria->getMappingNodeKeyFilter();
        $entityTypeFilter = $criteria->getEntityTypeFilter();
        $externalIdFilter = $criteria->getExternalIdFilter();
        $portalNodeKeyFilter = $criteria->getPortalNodeKeyFilter();

        if ($mappingNodeKeyFilter->count() > 0) {
            $mappingNodeIds = [];

            foreach ($mappingNodeKeyFilter as $mappingNodeKey) {
                if (!$mappingNodeKey instanceof MappingNodeStorageKey) {
                    throw new InvalidOverviewCriteriaException($criteria, 1643877525, new UnsupportedStorageKeyException(\get_class($mappingNodeKey)));
                }

                $mappingNodeIds[] = Id::toBinary($mappingNodeKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('mapping_node.id', ':mappingNodeIds'));
            $builder->setParameter('mappingNodeIds', $mappingNodeIds, Connection::PARAM_STR_ARRAY);
        }

        if ($entityTypeFilter !== []) {
            $builder->andWhere($builder->expr()->in('entity_type.type', ':entityTypes'));
            $builder->setParameter('entityTypes', \array_map('strval', $entityTypeFilter), Connection::PARAM_STR_ARRAY);
        }

        if ($externalIdFilter !== []) {
            $builder->andWhere($builder->expr()->in('mapping.external_id', ':externalIds'));
            $builder->setParameter('externalIds', $externalIdFilter, Connection::PARAM_STR_ARRAY);
        }

        if ($portalNodeKeyFilter->count() > 0) {
            $portalNodeIds = [];

            foreach ($portalNodeKeyFilter as $portalNodeKey) {
                $portalNodeKey = $portalNodeKey->withoutAlias();

                if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                    throw new InvalidOverviewCriteriaException($criteria, 1643877526, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
                }

                $portalNodeIds[] = Id::toBinary($portalNodeKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('portal_node.id', ':portalNodeIds'));
            $builder->setParameter('portalNodeIds', $portalNodeIds, Connection::PARAM_STR_ARRAY);
        }

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === IdentityOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case IdentityOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'mapping.created_at';

                    break;
                case IdentityOverviewCriteria::FIELD_ENTITY_TYPE:
                    $dbalFieldName = 'entity_type.type';

                    break;
                case IdentityOverviewCriteria::FIELD_EXTERNAL_ID:
                    $dbalFieldName = 'mapping.external_id';

                    break;
                case IdentityOverviewCriteria::FIELD_MAPPING_NODE:
                    $dbalFieldName = 'mapping_node.id';

                    break;
                case IdentityOverviewCriteria::FIELD_PORTAL_NODE:
                    $dbalFieldName = 'portal_node.id';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1643877527);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('mapping.id', 'ASC');

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult($page * $pageSize);
            }
        }

        return \iterable_map(
            $builder->iterateRows(),
            static fn (array $row): IdentityOverviewResult => new IdentityOverviewResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['portal_node_id'])),
                new MappingNodeStorageKey(Id::toHex((string) $row['mapping_node_id'])),
                (string) $row['mapping_external_id'],
                new UnsafeClassString((string) $row['entity_type_type']),
                /* @phpstan-ignore-next-line */
                DateTime::fromStorage((string) $row['created_at'])
            )
        );
    }

    protected function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::OVERVIEW_QUERY);

        $builder->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_portal_node',
                'portal_node',
                $builder->expr()->eq('mapping.portal_node_id', 'portal_node.id')
            )
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->innerJoin(
                'mapping_node',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('mapping_node.type_id', 'entity_type.id')
            )
            ->select([
                'portal_node.id portal_node_id',
                'mapping_node.id mapping_node_id',
                'mapping.external_id mapping_external_id',
                'entity_type.type entity_type_type',
                'mapping.created_at created_at',
            ])
            ->andWhere($builder->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'));

        return $builder;
    }
}
