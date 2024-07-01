<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Overview\PortalNodeOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Utility\ClassString\Contract\ClassStringReferenceContract;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;

final class PortalNodeOverview implements PortalNodeOverviewActionInterface
{
    public const string OVERVIEW_QUERY = '478b14da-d0a8-44fd-bd1a-0a60ef948dd7';

    private ?SelectQueryBuilder $builder = null;

    public function __construct(
        private readonly QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function overview(PortalNodeOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $classNameFilter = $criteria->getClassNameFilter();

        if ($classNameFilter->count() > 0) {
            $classNames = \iterable_to_array($classNameFilter->map(
                static fn (ClassStringReferenceContract $type): string => (string) $type
            ));
            $builder->andWhere($builder->expr()->in('portal_node.class_name', ':classNames'));
            $builder->setParameter('classNames', $classNames, ArrayParameterType::STRING);
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
                $builder->setFirstResult(($page - 1) * $pageSize);
            }
        }

        /** @var array{id: string, portal_node_class_name: string, created_at: string} $row */
        foreach ($builder->iterateRows() as $row) {
            yield new PortalNodeOverviewResult(
                new PortalNodeStorageKey(Id::toHex($row['id'])),
                new UnsafeClassString($row['portal_node_class_name']),
                /* @phpstan-ignore-next-line */
                DateTime::fromStorage((string) $row['created_at']),
            );
        }
    }

    private function getBuilderCached(): SelectQueryBuilder
    {
        if (!$this->builder instanceof SelectQueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::OVERVIEW_QUERY);

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
