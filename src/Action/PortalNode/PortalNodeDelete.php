<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Delete\PortalNodeDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Defaults;

class PortalNodeDelete implements PortalNodeDeleteActionInterface
{
    private ?QueryBuilder $deleteBuilder = null;

    private ?QueryBuilder $searchBuilder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function delete(PortalNodeDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $ids[] = \hex2bin($portalNodeKey->getUuid());
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

        $this->deleteBuilder = $builder = new QueryBuilder($this->connection);

        $builder->update('heptaconnect_portal_node');
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

        $this->searchBuilder = $builder = new QueryBuilder($this->connection);

        $builder->from('heptaconnect_portal_node');
        $builder->select('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));
        $builder->andWhere($builder->expr()->isNull('deleted_at'));

        return $builder;
    }
}
