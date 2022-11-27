<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Listing\PortalNodeListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeListActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

final class PortalNodeList implements PortalNodeListActionInterface
{
    public const LIST_QUERY = '52e85ba9-3610-403b-be28-b8d138481ace';

    private ?QueryBuilder $searchBuilder = null;

    private QueryFactory $queryFactory;

    private QueryIterator $queryIterator;

    public function __construct(QueryFactory $queryFactory, QueryIterator $queryIterator)
    {
        $this->queryFactory = $queryFactory;
        $this->queryIterator = $queryIterator;
    }

    public function list(): iterable
    {
        return \iterable_map(
            $this->queryIterator->iterateColumn($this->getSearchQuery()),
            static fn (string $id) => new PortalNodeListResult(new PortalNodeStorageKey(Id::toHex($id)))
        );
    }

    private function getSearchQuery(): QueryBuilder
    {
        $builder = $this->searchBuilder;

        if ($builder instanceof QueryBuilder) {
            return clone $builder;
        }

        $this->searchBuilder = $builder = $this->queryFactory->createBuilder(self::LIST_QUERY);

        $builder->from('heptaconnect_portal_node');
        $builder->addOrderBy('id');
        $builder->select('id');
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
