<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\Route\Get\RouteGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\RouteGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;

final readonly class RouteGet implements RouteGetActionInterface
{
    public const string FETCH_QUERY = '24ab04cd-03f5-40c8-af25-715856281314';

    public function __construct(
        private QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function get(RouteGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getRouteKeys() as $routeKey) {
            if (!$routeKey instanceof RouteStorageKey) {
                throw new UnsupportedStorageKeyException($routeKey);
            }

            $ids[] = $routeKey->getUuid();
        }

        return $ids === [] ? [] : $this->yieldRoutes($ids);
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::FETCH_QUERY);

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
            ->leftJoin(
                'route',
                'heptaconnect_route_configuration',
                'route_config',
                $builder->expr()->and(
                    $builder->expr()->eq('route_config.route_id', 'route.id'),
                    $builder->expr()->eq('route_config.value', ':configValue'),
                    $builder->expr()->eq('route_config.type', ':configType'),
                )
            )
            ->select([
                'route.id id',
                'entity_type.name entity_type_name',
                'source_portal_node.id source_portal_node_id',
                'target_portal_node.id target_portal_node_id',
                'GROUP_CONCAT(route_config.value SEPARATOR \',\') capability_name',
            ])
            ->addGroupBy([
                'route.id',
                'entity_type.name',
                'source_portal_node.id',
                'target_portal_node.id',
            ])
            ->setParameter('configType', 'bool')
            ->setParameter('configValue', 'true')
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->in('route.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<RouteGetResult>
     */
    private function yieldRoutes(array $ids): iterable
    {
        $builder = $this->getBuilder();
        $builder->setParameter('ids', Id::toBinaryList($ids), ArrayParameterType::STRING);

        /**
         * @var array{
         *     id: string,
         *     entity_type_name: string,
         *     source_portal_node_id: string,
         *     target_portal_node_id: string,
         *     capability_name: string|null
         * } $row
         **/
        foreach ($builder->iterateRows('route.id') as $row) {
            yield new RouteGetResult(
                new RouteStorageKey(Id::toHex($row['id'])),
                new PortalNodeStorageKey(Id::toHex($row['source_portal_node_id'])),
                new PortalNodeStorageKey(Id::toHex($row['target_portal_node_id'])),
                new UnsafeClassString($row['entity_type_name']),
                \explode(',', (string) $row['capability_name'])
            );
        }
    }
}
