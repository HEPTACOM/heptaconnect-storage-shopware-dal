<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Dataset\Base\UnsafeClassString;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

final class PortalNodeGet implements PortalNodeGetActionInterface
{
    public const FETCH_QUERY = 'efbd19ba-bc8e-412c-afb2-8a21f35e21f9';

    private ?QueryBuilder $builder = null;

    public function __construct(
        private QueryFactory $queryFactory,
        private QueryIterator $iterator
    ) {
    }

    public function get(PortalNodeGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeKey = $portalNodeKey->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException($portalNodeKey::class);
            }

            $ids[] = $portalNodeKey->getUuid();
        }

        return $ids === [] ? [] : $this->iteratePortalNodes($ids);
    }

    private function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    private function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);

        return $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.class_name portal_node_class_name',
            ])
            ->orderBy('id')
            ->where(
                $builder->expr()->isNull('portal_node.deleted_at'),
                $builder->expr()->in('portal_node.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<\Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult>
     */
    private function iteratePortalNodes(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Id::toBinaryList($ids), Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $this->iterator->iterate($builder),
            static fn (array $row): PortalNodeGetResult => new PortalNodeGetResult(
                new PortalNodeStorageKey(Id::toHex((string) $row['id'])),
                new UnsafeClassString((string) $row['portal_node_class_name'])
            )
        );
    }
}
