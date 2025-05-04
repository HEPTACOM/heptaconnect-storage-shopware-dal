<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Listing\ReceptionRouteListResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\ReceptionRouteListActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Enum\RouteCapability;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final readonly class ReceptionRouteList implements ReceptionRouteListActionInterface
{
    public const string LIST_QUERY = 'a2dc9481-5738-448a-9c85-617fec45a00d';

    public function __construct(
        private QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function list(ReceptionRouteListCriteria $criteria): iterable
    {
        $sourceKey = $criteria->getSourcePortalNodeKey()->withoutAlias();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($sourceKey);
        }

        $builder = $this->getBuilder();

        $builder->setParameter('source_key', Id::toBinary($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', (string) $criteria->getEntityType());
        $builder->setParameter('capability', RouteCapability::RECEPTION);

        foreach ($builder->iterateColumn('route.id') as $id) {
            yield new ReceptionRouteListResult(new RouteStorageKey(Id::toHex($id)));
        }
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::LIST_QUERY);

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
            ->innerJoin(
                'route',
                'heptaconnect_route_has_capability',
                'route_has_capability',
                $builder->expr()->eq('route_has_capability.route_id', 'route.id')
            )
            ->innerJoin(
                'route_has_capability',
                'heptaconnect_route_capability',
                'capability',
                (string) $builder->expr()->and(
                    $builder->expr()->eq('capability.id', 'route_has_capability.route_capability_id'),
                    $builder->expr()->isNull('capability.deleted_at')
                )
            )
            ->select(['route.id id'])
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->isNull('source_portal_node.deleted_at'),
                $builder->expr()->isNull('target_portal_node.deleted_at'),
                $builder->expr()->eq('route.source_id', ':source_key'),
                $builder->expr()->eq('entity_type.name', ':type'),
                $builder->expr()->eq('capability.name', ':capability')
            );
    }
}
