<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeConfiguration\Get\PortalNodeConfigurationGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeConfiguration\PortalNodeConfigurationGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\ReadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class PortalNodeConfigurationGet implements PortalNodeConfigurationGetActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function get(PortalNodeConfigurationGetCriteria $criteria): iterable
    {
        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if ($portalNodeKey instanceof PreviewPortalNodeKey) {
                continue;
            }

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }
        }

        $portalNodeIds = [];

        /** @var PortalNodeStorageKey $portalNodeKey */
        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if ($portalNodeKey instanceof PreviewPortalNodeKey) {
                continue;
            }

            $portalNodeIds[] = \hex2bin($portalNodeKey->getUuid());
        }

        if ($portalNodeIds === []) {
            return [];
        }

        $builder = $this->connection->createQueryBuilder();

        $builder->from('heptaconnect_portal_node', 'p')
            ->andWhere($builder->expr()->in('p.id', ':ids'))
            ->andWhere($builder->expr()->isNull('p.deleted_at'))
            ->select([
                'p.id portal_node_id',
                'p.configuration portal_configuration',
            ])
            ->setParameter('ids', $portalNodeIds, Connection::PARAM_STR_ARRAY);

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1642863471);
        }

        return \iterable_map(
            $statement->fetchAll(FetchMode::ASSOCIATIVE),
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

                return new PortalNodeConfigurationGetResult(
                    new PortalNodeStorageKey($portalNodeId),
                    /* @phpstan-ignore-next-line */
                    (array) $value,
                );
            }
        );
    }
}
