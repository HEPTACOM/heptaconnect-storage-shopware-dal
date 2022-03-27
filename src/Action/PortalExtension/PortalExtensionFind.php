<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalExtension\Find\PortalExtensionFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\PortalExtensionFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class PortalExtensionFind implements PortalExtensionFindActionInterface
{
    public const LOOKUP_QUERY = '82bb12c6-ed9c-4646-901a-4ff7e8e4e88c';

    private ?QueryBuilder $queryBuilder = null;

    private QueryFactory $queryFactory;

    public function __construct(QueryFactory $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function find(PortalNodeKeyInterface $portalNodeKey): PortalExtensionFindResult
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $builder = $this->getQueryBuilder()->setParameter('portalNodeId', Id::toBinary($portalNodeId), Types::BINARY);
        $result = new PortalExtensionFindResult();

        foreach ($builder->iterateRows() as $extension) {
            $result->add((string) $extension['class_name'], (bool) $extension['active']);
        }

        return $result;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        if (!$this->queryBuilder instanceof QueryBuilder) {
            $this->queryBuilder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);
            $expr = $this->queryBuilder->expr();

            $this->queryBuilder
                ->select([
                    'portal_node_extension.class_name',
                    'portal_node_extension.active',
                ])
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->where($expr->eq('portal_node_id', ':portalNodeId'))
                ->addOrderBy('portal_node_extension.id')
            ;
        }

        return $this->queryBuilder;
    }
}
