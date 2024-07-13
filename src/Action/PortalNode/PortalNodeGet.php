<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;

final class PortalNodeGet implements PortalNodeGetActionInterface
{
    public const string FETCH_QUERY = 'efbd19ba-bc8e-412c-afb2-8a21f35e21f9';

    private ?SelectQueryBuilder $builder = null;

    public function __construct(
        private readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function get(PortalNodeGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeKey = $portalNodeKey->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($portalNodeKey));
            }

            $ids[] = $portalNodeKey->getUuid();
        }

        return $ids === [] ? [] : $this->iteratePortalNodes($ids);
    }

    private function getBuilderCached(): SelectQueryBuilder
    {
        if (!$this->builder instanceof SelectQueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::FETCH_QUERY);

        return $builder
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.class_name portal_node_class_name',
            ])
            ->where(
                $builder->expr()->isNull('portal_node.deleted_at'),
                $builder->expr()->in('portal_node.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<PortalNodeGetResult>
     */
    private function iteratePortalNodes(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Id::toBinaryList($ids), ArrayParameterType::STRING);

        /** @var array{id: string, portal_node_class_name: string} $row */
        foreach ($builder->iterateRows('id') as $row) {
            yield new PortalNodeGetResult(
                new PortalNodeStorageKey(Id::toHex($row['id'])),
                new UnsafeClassString($row['portal_node_class_name'])
            );
        }
    }
}
