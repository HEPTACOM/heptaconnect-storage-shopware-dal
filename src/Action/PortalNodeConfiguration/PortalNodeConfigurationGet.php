<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\ReadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final readonly class PortalNodeConfigurationGet implements PortalNodeConfigurationGetActionInterface
{
    public const string FETCH_QUERY = 'be4a9934-2ab2-4c62-8a86-4600c96bc7be';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function get(PortalNodeConfigurationGetCriteria $criteria): iterable
    {
        $portalNodeIds = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeKey = $portalNodeKey->withoutAlias();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($portalNodeKey));
            }

            $portalNodeIds[] = Id::toBinary($portalNodeKey->getUuid());
        }

        if ($portalNodeIds === []) {
            return [];
        }

        $builder = $this->queryFactory->createSelectBuilder(self::FETCH_QUERY);

        $builder->from('heptaconnect_portal_node', 'p')
            ->andWhere($builder->expr()->in('p.id', ':ids'))
            ->andWhere($builder->expr()->isNull('p.deleted_at'))
            ->select([
                'p.id portal_node_id',
                'p.configuration portal_configuration',
            ])
            ->addOrderBy('p.id')
            ->setParameter('ids', $portalNodeIds, ArrayParameterType::STRING);

        /** @var array{portal_node_id: string, portal_configuration: string|null} $row */
        foreach ($builder->iterateRows() as $row) {
            $portalNodeId = Id::toHex($row['portal_node_id']);

            try {
                $value = \json_decode((string) $row['portal_configuration'], true, \JSON_THROW_ON_ERROR, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new ReadException('portal node configuration for ' . $portalNodeId, 1642863472, $exception);
            }

            if (!\is_array($value)) {
                throw new ReadException('portal node configuration for ' . $portalNodeId, 1642863473);
            }

            yield new PortalNodeConfigurationGetResult(new PortalNodeStorageKey($portalNodeId), $value);
        }
    }
}
