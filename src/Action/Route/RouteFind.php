<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Route;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\ParameterType;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Route\Find\RouteFindResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteFind implements RouteFindActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function find(RouteFindCriteria $criteria): ?RouteFindResult
    {
        $sourceKey = $criteria->getSource();

        if (!$sourceKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($sourceKey));
        }

        $targetKey = $criteria->getSource();

        if (!$targetKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($targetKey));
        }

        $builder = $this->getBuilderCached();

        $builder->setParameter('source_key', Uuid::fromHexToBytes($sourceKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('target_key', Uuid::fromHexToBytes($targetKey->getUuid()), ParameterType::BINARY);
        $builder->setParameter('type', $criteria->getEntityType());

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1637467906);
        }

        $id = $statement->fetchColumn();

        if (!\is_string($id)) {
            return null;
        }

        return new RouteFindResult(new RouteStorageKey(Uuid::fromBytesToHex($id)));
    }

    protected function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = new QueryBuilder($this->connection);

        return $builder
            ->from('heptaconnect_route', 'route')
            ->innerJoin(
                'route',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('entity_type.id', 'route.type_id')
            )
            ->select(['route.id id'])
            ->setMaxResults(1)
            ->where(
                $builder->expr()->isNull('route.deleted_at'),
                $builder->expr()->eq('route.source_id', ':source_key'),
                $builder->expr()->eq('route.target_id', ':target_key'),
                $builder->expr()->eq('entity_type.type', ':type'),
            );
    }
}
