<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Overview\RouteOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Utility\ClassString\ClassStringReferenceCollection;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;
use Heptacom\HeptaConnect\Utility\Collection\Scalar\StringCollection;

final readonly class RouteOverview implements RouteOverviewActionInterface
{
    public const string OVERVIEW_QUERY = '6cb18ac6-6f5a-4d31-bed3-44849eb51f6f';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function overview(RouteOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilder();
        $capabilityFilter = $criteria->getCapabilityFilter();

        if ($capabilityFilter !== null) {
            $builder->andWhere($builder->expr()->in('capability.name', ':caps'));
            $builder->setParameter('caps', $capabilityFilter->asArray(), ArrayParameterType::STRING);
        }

        $portalNodeKeys = $criteria->getSourcePortalNodeKeyFilter();

        if ($portalNodeKeys instanceof PortalNodeKeyCollection) {
            $portalNodeIds = [];

            foreach ($portalNodeKeys as $portalNodeKey) {
                if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                    throw new UnsupportedStorageKeyException($portalNodeKey);
                }

                $portalNodeIds[] = $portalNodeKey->getUuid();
            }

            $builder->andWhere($builder->expr()->in('source_portal_node.id', ':sourcePortals'));
            $builder->setParameter('sourcePortals', Id::toBinaryList($portalNodeIds), ArrayParameterType::STRING);
        }

        $portalNodeKeys = $criteria->getTargetPortalNodeKeyFilter();

        if ($portalNodeKeys instanceof PortalNodeKeyCollection) {
            $portalNodeIds = [];

            foreach ($portalNodeKeys as $portalNodeKey) {
                if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                    throw new UnsupportedStorageKeyException($portalNodeKey);
                }

                $portalNodeIds[] = $portalNodeKey->getUuid();
            }

            $builder->andWhere($builder->expr()->in('target_portal_node.id', ':targetPortals'));
            $builder->setParameter('targetPortals', Id::toBinaryList($portalNodeIds), ArrayParameterType::STRING);
        }

        $entityTypes = $criteria->getEntityTypeFilter();

        if ($entityTypes instanceof ClassStringReferenceCollection) {
            $entities = [];

            foreach ($entityTypes as $entityType) {
                $entities[] = (string) $entityType;
            }

            $builder->andWhere($builder->expr()->in('entity_type.type', ':entities'));
            $builder->setParameter('entities', $entities, ArrayParameterType::STRING);
        }

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === RouteOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case RouteOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'route.created_at';

                    break;
                case RouteOverviewCriteria::FIELD_ENTITY_TYPE:
                    $dbalFieldName = 'entity_type.type';

                    break;
                case RouteOverviewCriteria::FIELD_SOURCE:
                    // TODO allow sort by portal name
                    $dbalFieldName = 'source_portal_node.class_name';

                    break;
                case RouteOverviewCriteria::FIELD_TARGET:
                    // TODO allow sort by portal name
                    $dbalFieldName = 'target_portal_node.class_name';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1636528918);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult(($page - 1) * $pageSize);
            }
        }

        /**
         * @var array{
         *     id: string,
         *     entity_type_name: string,
         *     source_portal_node_id: string,
         *     source_portal_node_class: string,
         *     target_portal_node_id: string,
         *     target_portal_node_class: string,
         *     ct: string,
         *     capability_name: string|null
         * } $row
         */
        foreach ($builder->iterateRows('route.id') as $row) {
            yield new RouteOverviewResult(
                new RouteStorageKey(Id::toHex($row['id'])),
                new UnsafeClassString($row['entity_type_name']),
                new PortalNodeStorageKey(Id::toHex($row['source_portal_node_id'])),
                new UnsafeClassString($row['source_portal_node_class']),
                new PortalNodeStorageKey(Id::toHex($row['target_portal_node_id'])),
                new UnsafeClassString($row['target_portal_node_class']),
                /* @phpstan-ignore-next-line */
                DateTime::fromStorage((string) $row['ct']),
                new StringCollection(\explode(',', (string) $row['capability_name']))
            );
        }
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::OVERVIEW_QUERY);

        return $builder
            ->from('heptaconnect_route', 'route')
            ->innerJoin(
                'route',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('entity_type.id', 'route.type_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'source_portal_node',
                $builder->expr()->eq('source_portal_node.id', 'route.source_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'target_portal_node',
                $builder->expr()->eq('target_portal_node.id', 'route.target_id')
            )
            ->leftJoin(
                'route',
                'heptaconnect_route_has_capability',
                'route_has_capability',
                $builder->expr()->eq('route_has_capability.route_id', 'route.id')
            )
            ->leftJoin(
                'route_has_capability',
                'heptaconnect_route_capability',
                'capability',
                (string) $builder->expr()->and(
                    $builder->expr()->eq('route_has_capability.route_capability_id', 'capability.id'),
                    $builder->expr()->isNull('capability.deleted_at')
                )
            )
            ->select([
                'route.id id',
                'entity_type.type entity_type_name',
                'source_portal_node.id source_portal_node_id',
                'source_portal_node.class_name source_portal_node_class',
                'target_portal_node.id target_portal_node_id',
                'target_portal_node.class_name target_portal_node_class',
                'route.created_at ct',
                'GROUP_CONCAT(capability.name SEPARATOR \',\') capability_name',
            ])
            ->groupBy([
                'route.id',
                'entity_type.type',
                'source_portal_node.id',
                'source_portal_node.class_name',
                'target_portal_node.id',
                'target_portal_node.class_name',
                'route.created_at',
            ])
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->isNull('source_portal_node.deleted_at'),
                $builder->expr()->isNull('target_portal_node.deleted_at')
            );
    }
}
