<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Overview\PortalNodeAliasOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Overview\PortalNodeAliasOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final class PortalNodeAliasOverview implements PortalNodeAliasOverviewActionInterface
{
    public const string OVERVIEW_QUERY = '8467ced0-3575-410f-8155-e36e7e8f0e0b';

    public function __construct(
        private readonly QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function overview(PortalNodeAliasOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilder();

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === PortalNodeAliasOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case PortalNodeAliasOverviewCriteria::FIELD_ALIAS:
                    $dbalFieldName = 'portal_node.alias';

                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1647941560);
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

        /** @var array{id: string, alias: string} $row */
        foreach ($builder->iterateRows('portal_node.id') as $row) {
            yield new PortalNodeAliasOverviewResult(
                new PortalNodeStorageKey(Id::toHex($row['id'])),
                $row['alias']
            );
        }
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::OVERVIEW_QUERY);

        return $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.alias alias',
            ])
            ->where($builder->expr()->isNotNull('alias'))
            ->andWhere($builder->expr()->isNull('deleted_at'));
    }
}
