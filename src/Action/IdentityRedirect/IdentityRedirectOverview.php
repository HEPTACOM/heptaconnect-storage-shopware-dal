<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityRedirect;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\ScalarCollection\StringCollection;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Identity\Overview\IdentityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Overview\IdentityRedirectOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Overview\IdentityRedirectOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityRedirect\IdentityRedirectOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\IdentityRedirectKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityRedirectStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityRedirectOverview implements IdentityRedirectOverviewActionInterface
{
    public const OVERVIEW_QUERY = '832dbfc9-4939-4301-ade4-aa73d961454f';

    private ?QueryBuilder $builder = null;

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    public function overview(IdentityRedirectOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $identityRedirectKeyFilter = $criteria->getIdentityRedirectKeyFilter();
        $entityTypeFilter = $criteria->getEntityTypeFilter();
        $sourcePortalNodeKeyFilter = $criteria->getSourcePortalNodeKeyFilter();
        $sourceExternalIdFilter = $criteria->getSourceExternalIdFilter();
        $targetPortalNodeKeyFilter = $criteria->getTargetPortalNodeKeyFilter();
        $targetExternalIdFilter = $criteria->getTargetExternalIdFilter();

        if ($identityRedirectKeyFilter instanceof IdentityRedirectKeyCollection) {
            $identityRedirectIds = [];

            foreach ($identityRedirectKeyFilter as $identityRedirectKey) {
                if (!$identityRedirectKey instanceof IdentityRedirectStorageKey) {
                    throw new InvalidOverviewCriteriaException($criteria, 1673729808, new UnsupportedStorageKeyException(\get_class($identityRedirectKey)));
                }

                $identityRedirectIds[] = Id::toBinary($identityRedirectKey->getUuid());
            }

            $builder->andWhere($builder->expr()->in('identity_redirect.id', ':identityRedirectIds'));
            $builder->setParameter('identityRedirectIds', $identityRedirectIds, Connection::PARAM_STR_ARRAY);
        }

        if ($entityTypeFilter instanceof StringCollection) {
            $builder->andWhere($builder->expr()->in('entity_type.type', ':entityTypes'));
            $builder->setParameter('entityTypes', $entityTypeFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($sourceExternalIdFilter instanceof StringCollection) {
            $builder->andWhere($builder->expr()->in('identity_redirect.source_external_id', ':sourceExternalIds'));
            $builder->setParameter('sourceExternalIds', $sourceExternalIdFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($targetExternalIdFilter instanceof StringCollection) {
            $builder->andWhere($builder->expr()->in('identity_redirect.target_external_id', ':targetExternalIds'));
            $builder->setParameter('targetExternalIds', $targetExternalIdFilter->asArray(), Connection::PARAM_STR_ARRAY);
        }

        if ($sourcePortalNodeKeyFilter instanceof PortalNodeKeyCollection) {
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

        if ($targetPortalNodeKeyFilter instanceof PortalNodeKeyCollection) {
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
            $dbalFieldName = match ($field) {
                IdentityRedirectOverviewCriteria::FIELD_CREATED => 'identity_redirect.created_at',
                IdentityRedirectOverviewCriteria::FIELD_ENTITY_TYPE => 'entity_type.type',
                IdentityRedirectOverviewCriteria::FIELD_SOURCE_EXTERNAL_ID => 'identity_redirect.source_external_id',
                IdentityRedirectOverviewCriteria::FIELD_TARGET_EXTERNAL_ID => 'identity_redirect.target_external_id',
                IdentityRedirectOverviewCriteria::FIELD_SOURCE_PORTAL_NODE => 'source_portal_node.id',
                IdentityRedirectOverviewCriteria::FIELD_TARGET_PORTAL_NODE => 'target_portal_node.id',
                default => throw new InvalidOverviewCriteriaException($criteria, 1673729811),
            };

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('identity_redirect.id', 'ASC');

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
            static fn (array $row): IdentityRedirectOverviewResult => new IdentityRedirectOverviewResult(
                new IdentityRedirectStorageKey(Id::toHex((string) $row['identity_redirect'])),
                new PortalNodeStorageKey(Id::toHex((string) $row['source_portal_node_id'])),
                (string) $row['identity_redirect_source_external_id'],
                new PortalNodeStorageKey(Id::toHex((string) $row['target_portal_node_id'])),
                (string) $row['identity_redirect_target_external_id'],
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

        $builder->from('heptaconnect_identity_redirect', 'identity_redirect')
            ->innerJoin(
                'identity_redirect',
                'heptaconnect_portal_node',
                'source_portal_node',
                $builder->expr()->eq('identity_redirect.source_portal_node_id', 'source_portal_node.id')
            )
            ->innerJoin(
                'identity_redirect',
                'heptaconnect_portal_node',
                'target_portal_node',
                $builder->expr()->eq('identity_redirect.target_portal_node_id', 'target_portal_node.id')
            )
            ->innerJoin(
                'identity_redirect',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('identity_redirect.type_id', 'entity_type.id')
            )
            ->select([
                'identity_redirect.id identity_redirect_id',
                'source_portal_node.id source_portal_node_id',
                'identity_redirect.source_external_id identity_redirect_source_external_id',
                'target_portal_node.id target_portal_node_id',
                'identity_redirect.target_external_id identity_redirect_target_external_id',
                'entity_type.type entity_type_type',
                'identity_redirect.created_at created_at',
            ])
            ->andWhere($builder->expr()->isNull('source_portal_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('target_portal_node.deleted_at'))
        ;

        return $builder;
    }
}
