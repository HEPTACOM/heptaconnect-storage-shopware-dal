<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityDirection;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityDirection\Delete\IdentityDirectionDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityDirection\IdentityDirectionDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityDirectionStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class IdentityDirectionDelete implements IdentityDirectionDeleteActionInterface
{
    public const LOOKUP_QUERY = '26f18fa9-9246-45cf-b7f7-2fc80f61151d';

    public const DELETE_QUERY = 'ca54ecac-3b6b-4f54-882e-fea1f19336ba';

    private ?QueryBuilder $deleteBuilder = null;

    private ?QueryBuilder $searchBuilder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function delete(IdentityDirectionDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getIdentityDirectionKeys() as $identityDirectionKey) {
            if (!$identityDirectionKey instanceof IdentityDirectionStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($identityDirectionKey));
            }

            $ids[] = Id::toBinary($identityDirectionKey->getUuid());
        }

        if ($ids === []) {
            return;
        }

        $searchBuilder = $this->getSearchQuery();
        $searchBuilder->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
        $foundIds = \iterable_to_array($searchBuilder->iterateColumn());

        foreach ($ids as $id) {
            if (!\in_array($id, $foundIds, true)) {
                throw new NotFoundException();
            }
        }

        $deleteBuilder = $this->getDeleteQuery();
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

        $builder->delete('heptaconnect_identity_direction');
        $builder->andWhere($builder->expr()->in('id', ':ids'));

        return $builder;
    }

    protected function getSearchQuery(): QueryBuilder
    {
        $builder = $this->searchBuilder;

        if ($builder instanceof QueryBuilder) {
            return clone $builder;
        }

        $this->searchBuilder = $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        $builder->from('heptaconnect_identity_direction');
        $builder->select('id');
        $builder->addOrderBy('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));

        return $builder;
    }
}
