<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Find\PortalNodeAliasFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class PortalNodeAliasFind implements PortalNodeAliasFindActionInterface
{
    private Connection $connection;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(Connection $connection, StorageKeyGeneratorContract $storageKeyGenerator)
    {
        $this->connection = $connection;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function find(PortalNodeAliasFindCriteria $criteria): iterable
    {
        $identifiers = [];
        foreach ($criteria->getAlias() as $identifier) {
            try {
                /** @var PortalNodeStorageKey $portalNodeStorageKey */
                $portalNodeStorageKey = $this->storageKeyGenerator->deserialize($identifier);
                $identifiers[] = \hex2bin($portalNodeStorageKey->getUuid());
            } catch (UnsupportedStorageKeyException $e) {
                $identifiers[] = $identifier;
            }
        }

        $builder = $this->connection->createQueryBuilder();

        $builder->from('heptaconnect_portal_node', 'p')
            ->where($builder->expr()->in('p.alias', ':identifier'))
            ->orWhere($builder->expr()->in('p.id', ':identifier'))
            ->andWhere($builder->expr()->isNull('p.deleted_at'))
            ->select([
                'p.id portal_node_id',
                'p.alias portal_node_alias',
            ])
            ->setParameter('identifier', $identifiers, Connection::PARAM_STR_ARRAY);

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1645446001);
        }

        $results = [];

        foreach ($statement->fetchAllAssociative() as $row) {
            $uuid = \bin2hex($row['portal_node_id']);
            $alias = $row['portal_node_alias'];
            $result = new PortalNodeAliasFindResult('PortalNode:' . $uuid, $alias);
            $results[] = $result;
        }

        return $results;
    }
}
