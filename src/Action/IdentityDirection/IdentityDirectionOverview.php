<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityDirection;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Overview\IdentityDirectionOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Overview\IdentityDirectionOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityDirection\IdentityDirectionOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\IdentityDirectionKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityDirectionStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityDirectionOverview implements IdentityDirectionOverviewActionInterface
{
    public const OVERVIEW_QUERY = '832dbfc9-4939-4301-ade4-aa73d961454f';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function overview(IdentityDirectionOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $identityDirectionKeyFilter = $criteria->getIdentityDirectionKeyFilter();
        $entityTypeFilter = $criteria->getEntityTypeFilter();
        $sourcePortalNodeKeyFilter = $criteria->getSourcePortalNodeKeyFilter();
        $sourceExternalIdFilter = $criteria->getSourceExternalIdFilter();
        $targetPortalNodeKeyFilter = $criteria->getTargetPortalNodeKeyFilter();
        $targetExternalIdFilter = $criteria->getTargetExternalIdFilter();

        if ($identityDirectionKeyFilter->count() > 0) {
            $identityDirectionIds = [];

            foreach ($identityDirectionKeyFilter as $identityDirectionKey) {
                if (!$identityDirectionKey instanceof IdentityDirectionKeyInterface) {
                    throw new InvalidOverviewCriteriaException($criteria, 1673729808, new UnsupportedStorageKeyException(\get_class($identityDirectionKey)));
                }

                $identityDirectionIds[] = Id::toBinary($identityDirectionKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('identity_direction.id', ':identityDirectionIds'));
            $builder->setParameter('identityDirectionIds', $identityDirectionIds, Connection::PARAM_STR_ARRAY);
        }

        if ($entityTypeFilter !== null) {
            $builder->andWhere($builder->expr()->in('entity_type.type', ':entityTypes'));
            $builder->setParameter('entityTypes', $entityTypeFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($sourceExternalIdFilter !== null) {
            $builder->andWhere($builder->expr()->in('identity_direction.source_external_id', ':sourceExternalIds'));
            $builder->setParameter('sourceExternalIds', $sourceExternalIdFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($targetExternalIdFilter !== null) {
            $builder->andWhere($builder->expr()->in('identity_direction.target_external_id', ':targetExternalIds'));
            $builder->setParameter('targetExternalIds', $targetExternalIdFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($sourcePortalNodeKeyFilter !== null) {
            $portalNodeIds = [];

            foreach ($sourcePortalNodeKeyFilter as $portalNodeKey) {
                $portalNodeKey = $portalNodeKey->withoutAlias();

                if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                    throw new InvalidOverviewCriteriaException($criteria, 1673729809, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
                }

                $portalNodeIds[] = Id::toBinary($portalNodeKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('source_portal_node.id', ':sourcePortalNodeIds'));
            $builder->setParameter('sourcePortalNodeIds', $portalNodeIds, Connection::PARAM_STR_ARRAY);
        }

        if ($targetPortalNodeKeyFilter !== null) {
            $portalNodeIds = [];

            foreach ($targetPortalNodeKeyFilter as $portalNodeKey) {
                $portalNodeKey = $portalNodeKey->withoutAlias();

                if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                    throw new InvalidOverviewCriteriaException($criteria, 1673729810, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
                }

                $portalNodeIds[] = Id::toBinary($portalNodeKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('target_portal_node.id', ':targetPortalNodeIds'));
            $builder->setParameter('targetPortalNodeIds', $portalNodeIds, Connection::PARAM_STR_ARRAY);
        }

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === IdentityOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case IdentityDirectionOverviewCriteria::FIELD_CREATED :
                    $dbalFieldName = 'identity_direction.created_at';

                    break;
                case IdentityDirectionOverviewCriteria::FIELD_ENTITY_TYPE:
                    $dbalFieldName = 'entity_type.type';

                    break;
                case IdentityDirectionOverviewCriteria::FIELD_SOURCE_EXTERNAL_ID:
                    $dbalFieldName = 'identity_direction.source_external_id';

                    break;
                case IdentityDirectionOverviewCriteria::FIELD_TARGET_EXTERNAL_ID:
                    $dbalFieldName = 'identity_direction.target_external_id';

                    break;
                case IdentityDirectionOverviewCriteria::FIELD_SOURCE_PORTAL_NODE:
                    $dbalFieldName = 'source_portal_node.id';

                    break;
                case IdentityDirectionOverviewCriteria::FIELD_TARGET_PORTAL_NODE:
                    $dbalFieldName = 'target_portal_node.id';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1673729811);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('identity_direction.id', 'ASC');

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult(($page - 1) * $pageSize);
            }
        }

        return \iterable_map(
            $builder->iterateRows(),
            static fn (array $row): IdentityDirectionOverviewResult => new IdentityDirectionOverviewResult(
                new IdentityDirectionStorageKey(Id::toHex((string) $row['identity_direction_id'])),
                new PortalNodeStorageKey(Id::toHex((string) $row['source_portal_node_id'])),
                (string) $row['identity_direction_source_external_id'],
                new PortalNodeStorageKey(Id::toHex((string) $row['target_portal_node_id'])),
                (string) $row['identity_direction_target_external_id'],
                (string) $row['entity_type_type'],
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

        $builder->from('heptaconnect_identity_direction', 'identity_direction')
            ->innerJoin(
                'identity_direction',
                'heptaconnect_portal_node',
                'source_portal_node',
                $builder->expr()->eq('identity_direction.source_portal_node_id', 'source_portal_node.id')
            )
            ->innerJoin(
                'identity_direction',
                'heptaconnect_portal_node',
                'target_portal_node',
                $builder->expr()->eq('identity_direction.target_portal_node_id', 'target_portal_node.id')
            )
            ->innerJoin(
                'identity_direction',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('identity_direction.type_id', 'entity_type.id')
            )
            ->select([
                'identity_direction.id identity_direction_id',
                'source_portal_node.id source_portal_node_id',
                'identity_direction.source_external_id identity_direction_source_external_id',
                'target_portal_node.id target_portal_node_id',
                'identity_direction.target_external_id identity_direction_target_external_id',
                'entity_type.type entity_type_type',
                'identity_direction.created_at created_at',
            ])
            ->andWhere($builder->expr()->isNull('portal_node.deleted_at'));

        return $builder;
    }
}
