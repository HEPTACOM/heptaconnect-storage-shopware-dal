<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Listing\PortalNodeListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeListActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final class PortalNodeList implements PortalNodeListActionInterface
{
    public const string LIST_QUERY = '52e85ba9-3610-403b-be28-b8d138481ace';

    private ?SelectQueryBuilder $searchBuilder = null;

    public function __construct(
        private readonly QueryFactory $queryFactory,
    ) {
    }

    public function list(): iterable
    {
        foreach ($this->getSearchQuery()->iterateColumn('id') as $id) {
            yield new PortalNodeListResult(new PortalNodeStorageKey(Id::toHex($id)));
        }
    }

    private function getSearchQuery(): SelectQueryBuilder
    {
        $builder = $this->searchBuilder;

        if ($builder instanceof SelectQueryBuilder) {
            return clone $builder;
        }

        $this->searchBuilder = $builder = $this->queryFactory->createSelectBuilder(self::LIST_QUERY);

        $builder->from('heptaconnect_portal_node');
        $builder->select('id');
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
