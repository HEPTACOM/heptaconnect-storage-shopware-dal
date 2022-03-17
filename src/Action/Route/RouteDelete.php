<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Delete\RouteDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Shopware\Core\Defaults;

class RouteDelete implements RouteDeleteActionInterface
{
    public const LOOKUP_QUERY = 'b270142d-c897-4d1d-bddb-7641fbfb95a2';

    public const DELETE_QUERY = '384f50ca-1e0a-464b-80fd-824fc83b87ca';

    private ?QueryBuilder $deleteBuilder = null;

    private ?QueryBuilder $searchBuilder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function delete(RouteDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getRouteKeys() as $routeKey) {
            if (!$routeKey instanceof RouteStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($routeKey));
            }

            $ids[] = \hex2bin($routeKey->getUuid());
        }

        if ($ids === []) {
            return;
        }

        $searchBuilder = $this->getSearchQuery();
        $searchBuilder->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $foundIds = $searchBuilder->execute()->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($ids as $id) {
            if (!\in_array($id, $foundIds, true)) {
                throw new NotFoundException();
            }
        }

        $deleteBuilder = $this->getDeleteQuery();
        $deleteBuilder->setParameter('now', (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT));
        $deleteBuilder->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $deleteBuilder->execute();
    }

    protected function getDeleteQuery(): QueryBuilder
    {
        $builder = $this->deleteBuilder;

        if ($builder instanceof QueryBuilder) {
            return clone $builder;
        }

        $this->deleteBuilder = $builder = $this->queryFactory->createBuilder(self::DELETE_QUERY);

        $builder->update('heptaconnect_route');
        $builder->set('deleted_at', ':now');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }

    protected function getSearchQuery(): QueryBuilder
    {
        $builder = $this->searchBuilder;

        if ($builder instanceof QueryBuilder) {
            return clone $builder;
        }

        $this->searchBuilder = $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        $builder->from('heptaconnect_route');
        $builder->select('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
