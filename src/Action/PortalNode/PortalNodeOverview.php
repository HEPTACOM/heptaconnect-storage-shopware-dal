<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\Contract\ClassStringReferenceContract;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeOverview implements PortalNodeOverviewActionInterface
{
    public const OVERVIEW_QUERY = '478b14da-d0a8-44fd-bd1a-0a60ef948dd7';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function overview(PortalNodeOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $classNameFilter = $criteria->getClassNameFilter();

        if ($classNameFilter->count() > 0) {
            $classNames =\iterable_to_array($classNameFilter->map(
                static fn (ClassStringReferenceContract $type): string => (string) $type
            ));
            $builder->andWhere($builder->expr()->in('portal_node.class_name', ':classNames'));
            $builder->setParameter('classNames', $classNames, Connection::PARAM_STR_ARRAY);
        }

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === PortalNodeOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case PortalNodeOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'portal_node.created_at';

                    break;
                case PortalNodeOverviewCriteria::FIELD_CLASS_NAME:
                    $dbalFieldName = 'portal_node.class_name';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1640405544);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('portal_node.id', 'ASC');

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
            static fn (array $row): PortalNodeOverviewResult => new PortalNodeOverviewResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['id'])),
                new UnsafeClassString((string) $row['portal_node_class_name']),
                /* @phpstan-ignore-next-line */
                DateTime::fromStorage((string) $row['created_at']),
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

        return $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.class_name portal_node_class_name',
                'portal_node.created_at created_at',
            ])
            ->where($builder->expr()->isNull('portal_node.deleted_at'));
    }
}
