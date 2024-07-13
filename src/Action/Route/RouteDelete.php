<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Delete\RouteDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final readonly class RouteDelete implements RouteDeleteActionInterface
{
    public const string LOOKUP_QUERY = 'b270142d-c897-4d1d-bddb-7641fbfb95a2';

    public const string DELETE_QUERY = '384f50ca-1e0a-464b-80fd-824fc83b87ca';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function delete(RouteDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getRouteKeys() as $routeKey) {
            if (!$routeKey instanceof RouteStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($routeKey));
            }

            $ids[] = Id::toBinary($routeKey->getUuid());
        }

        if ($ids === []) {
            return;
        }

        $searchBuilder = $this->getSearchQuery();
        $searchBuilder->setParameter('ids', $ids, ArrayParameterType::STRING);
        $foundIds = \iterable_to_array($searchBuilder->iterateColumn('id'));

        foreach ($ids as $id) {
            if (!\in_array($id, $foundIds, true)) {
                throw new NotFoundException();
            }
        }

        $deleteBuilder = $this->getDeleteQuery();
        $deleteBuilder->setParameter('now', DateTime::nowToStorage());
        $deleteBuilder->setParameter('ids', $ids, ArrayParameterType::STRING);
        $deleteBuilder->executeStatement();
    }

    private function getDeleteQuery(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::DELETE_QUERY);

        $builder->update('heptaconnect_route');
        $builder->set('deleted_at', ':now');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }

    private function getSearchQuery(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::LOOKUP_QUERY);

        $builder->from('heptaconnect_route');
        $builder->select('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
