<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Get\PortalNodeAliasGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Get\PortalNodeAliasGetResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasGetActionInterface;

class PortalNodeAliasGet implements PortalNodeAliasGetActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function get(PortalNodeAliasGetCriteria $criteria): iterable
    {
        $portalNodeIds = [];
        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            $portalNodeIds[] = \hex2bin($portalNodeKey->getUuid());
        }

        if ($portalNodeIds === []) {
            return [];
        }

        $builder = $this->connection->createQueryBuilder();

        $builder->from('heptaconnect_portal_node', 'p')
            ->andWhere($builder->expr()->in('p.id', ':ids'))
            ->andWhere($builder->expr()->isNull('p.deleted_at'))
            ->andWhere($builder->expr()->isNotNull('p.alias'))
            ->select([
                'p.id portal_node_id',
                'p.alias portal_node_alias',
            ])
            ->setParameter('ids', $portalNodeIds, Connection::PARAM_STR_ARRAY);

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1643294417);
        }

        $results = [];

        foreach ($statement->fetchAllAssociative() as $row) {
            $uuid = \bin2hex($row['portal_node_id']);
            $alias = $row['portal_node_alias'];
            $result = new PortalNodeAliasGetResult('PortalNode:' . $uuid, $alias);
            $results[] = $result;
        }

        return $results;
    }
}
