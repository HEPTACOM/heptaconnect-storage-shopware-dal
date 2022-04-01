<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalNodeDelete implements PortalNodeDeleteActionInterface
{
    public const DELETE_QUERY = '219156bb-0598-49df-8205-6d10e8f92a61';

    public const LOOKUP_QUERY = 'aafca974-b95e-46ea-a680-834a93d13140';

    private ?QueryBuilder $deleteBuilder = null;

    private ?QueryBuilder $searchBuilder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function delete(PortalNodeDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeKey = $portalNodeKey->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $ids[] = Id::toBinary($portalNodeKey->getUuid());
        }

        if ($ids === []) {
            return;
        }

        $searchBuilder = $this->getSearchQuery();
        $searchBuilder->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $idsCheck = \array_combine($ids, $ids);

        foreach ($searchBuilder->iterateRows() as $row) {
            $id = \current($row);
            unset($idsCheck[$id]);
        }

        if ($idsCheck !== []) {
            throw new NotFoundException();
        }

        $deleteBuilder = $this->getDeleteQuery();
        $deleteBuilder->setParameter('now', DateTime::nowToStorage());
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

        $builder->update('heptaconnect_portal_node');
        $builder->set('deleted_at', ':now');
        $builder->set('alias', ':alias');
        $builder->setParameter('alias', null);
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

        $builder->from('heptaconnect_portal_node');
        $builder->select('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));
        $builder->addOrderBy('id');

        return $builder;
    }
}
