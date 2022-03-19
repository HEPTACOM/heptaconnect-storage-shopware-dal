<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\ReadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class PortalNodeConfigurationGet implements PortalNodeConfigurationGetActionInterface
{
    public const FETCH_QUERY = 'be4a9934-2ab2-4c62-8a86-4600c96bc7be';

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function get(PortalNodeConfigurationGetCriteria $criteria): iterable
    {
        $portalNodeIds = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $portalNodeIds[] = \hex2bin($portalNodeKey->getUuid());
        }

        if ($portalNodeIds === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);

        $builder->from('heptaconnect_portal_node', 'p')
            ->andWhere($builder->expr()->in('p.id', ':ids'))
            ->andWhere($builder->expr()->isNull('p.deleted_at'))
            ->select([
                'p.id portal_node_id',
                'p.configuration portal_configuration',
            ])
            ->addOrderBy('p.id')
            ->setParameter('ids', $portalNodeIds, Connection::PARAM_STR_ARRAY);

        return \iterable_map(
            $builder->iterateRows(),
            static function (array $r): PortalNodeConfigurationGetResult {
                $portalNodeId = \bin2hex((string) $r['portal_node_id']);

                try {
                    $value = \json_decode((string) $r['portal_configuration'], true, \JSON_THROW_ON_ERROR);
                } catch (\JsonException $exception) {
                    throw new ReadException('portal node configuration for ' . $portalNodeId, 1642863472, $exception);
                }

                if (!\is_array($value)) {
                    throw new ReadException('portal node configuration for ' . $portalNodeId, 1642863473);
                }

                return new PortalNodeConfigurationGetResult(new PortalNodeStorageKey($portalNodeId), $value);
            }
        );
    }
}
