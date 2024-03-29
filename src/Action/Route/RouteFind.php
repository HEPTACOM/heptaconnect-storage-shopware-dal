<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class RouteFind implements RouteFindActionInterface
{
    public const LOOKUP_QUERY = '1f0d7c11-0d1c-4834-8b15-148d826d64e8';

    private ?QueryBuilder $builder = null;

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    public function find(RouteFindCriteria $criteria): ?RouteFindResult
    {
        $sourceKey = $criteria->getSource()->withoutAlias();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($sourceKey::class);
        }

        $targetKey = $criteria->getTarget()->withoutAlias();

        if (!$targetKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($targetKey::class);
        }

        $builder = $this->getBuilderCached();

        $builder->setParameter('source_key', Id::toBinary($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('target_key', Id::toBinary($targetKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', (string) $criteria->getEntityType());

        $id = $builder->fetchSingleValue();

        if (!\is_string($id)) {
            return null;
        }

        return new RouteFindResult(new RouteStorageKey(Id::toHex($id)));
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
        $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        return $builder
            ->from('heptaconnect_route', 'route')
            ->innerJoin(
                'route',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('entity_type.id', 'route.type_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'source_portal_node',
                $builder->expr()->eq('source_portal_node.id', 'route.source_id')
            )
            ->innerJoin(
                'route',
                'heptaconnect_portal_node',
                'target_portal_node',
                $builder->expr()->eq('target_portal_node.id', 'route.target_id')
            )
            ->select(['route.id id'])
            ->orderBy('route.id')
            ->setMaxResults(1)
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->isNull('source_portal_node.deleted_at'),
                $builder->expr()->isNull('target_portal_node.deleted_at'),
                $builder->expr()->eq('route.source_id', ':source_key'),
                $builder->expr()->eq('route.target_id', ':target_key'),
                $builder->expr()->eq('entity_type.type', ':type'),
            );
    }
}
