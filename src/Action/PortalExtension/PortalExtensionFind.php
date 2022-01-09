<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Find\PortalExtensionFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class PortalExtensionFind implements PortalExtensionFindActionInterface
{
    private Connection $connection;

    private ?QueryBuilder $queryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function find(PortalNodeKeyInterface $portalNodeKey): PortalExtensionFindResult
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();

        $extensions = $this->getQueryBuilder()
            ->setParameter('portalNodeId', \hex2bin($portalNodeId), Types::BINARY)
            ->execute()
            ->fetchAllAssociative()
        ;

        $result = new PortalExtensionFindResult();

        foreach ($extensions as $extension) {
            $result->add((string) $extension['class_name'], (bool) $extension['active']);
        }

        return $result;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        if (!$this->queryBuilder instanceof QueryBuilder) {
            $this->queryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->queryBuilder->expr();

            $this->queryBuilder
                ->select([
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->where($expr->eq('portal_node_id', ':portalNodeId'))
            ;
        }

        return $this->queryBuilder;
    }
}
