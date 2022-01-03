<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Listing\PortalNodeListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Listing\PortalNodeListActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class PortalNodeList implements PortalNodeListActionInterface
{
    private ?QueryBuilder $searchBuilder = null;

    private Connection $connection;

    private QueryIterator $queryIterator;

    public function __construct(Connection $connection, QueryIterator $queryIterator)
    {
        $this->connection = $connection;
        $this->queryIterator = $queryIterator;
    }

    public function list(): iterable
    {
        return \iterable_map(
            \iterable_map(
                $this->queryIterator->iterateColumn($this->getSearchQuery()),
                [Uuid::class, 'fromBytesToHex']
            ),
            static fn (string $id) => new PortalNodeListResult(new PortalNodeStorageKey($id))
        );
    }

    protected function getSearchQuery(): QueryBuilder
    {
        $builder = $this->searchBuilder;

        if ($builder instanceof QueryBuilder) {
            return clone $builder;
        }

        $this->searchBuilder = $builder = new QueryBuilder($this->connection);

        $builder->from('heptaconnect_portal_node');
        $builder->select('id');
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
