<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Get\PortalNodeGetResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class PortalNodeGet implements PortalNodeGetActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    private QueryIterator $iterator;

    public function __construct(Connection $connection, QueryIterator $iterator)
    {
        $this->connection = $connection;
        $this->iterator = $iterator;
    }

    public function get(PortalNodeGetCriteria $criteria): iterable
    {
        $ids = [];

        foreach ($criteria->getPortalNodeKeys() as $portalNodeKey) {
            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
            }

            $ids[] = $portalNodeKey->getUuid();
        }

        return $ids === [] ? [] : $this->iteratePortalNodes($ids);
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
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.class_name portal_node_class_name',
            ])
            ->orderBy('id')
            ->where(
                $builder->expr()->isNull('portal_node.deleted_at'),
                $builder->expr()->in('portal_node.id', ':ids')
            );
    }

    /**
     * @param string[] $ids
     *
     * @return iterable<PortalNodeGetResult>
     */
    protected function iteratePortalNodes(array $ids): iterable
    {
        $builder = $this->getBuilderCached();
        $builder->setParameter('ids', Uuid::fromHexToBytesList($ids), Connection::PARAM_STR_ARRAY);

        return $this->iterator->iterate($builder, static fn (array $row): PortalNodeGetResult => new PortalNodeGetResult(
            new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
            /* @phpstan-ignore-next-line */
            (string) $row['portal_node_class_name']
        ));
    }
}