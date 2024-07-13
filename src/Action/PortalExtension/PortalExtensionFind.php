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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;
use Heptacom\HeptaConnect\Utility\ClassString\UnsafeClassString;

final class PortalExtensionFind implements PortalExtensionFindActionInterface
{
    public const string LOOKUP_QUERY = '82bb12c6-ed9c-4646-901a-4ff7e8e4e88c';

    private ?SelectQueryBuilder $queryBuilder = null;

    public function __construct(
        private readonly QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function find(PortalNodeKeyInterface $portalNodeKey): PortalExtensionFindResult
    {
        $portalNodeKey = $portalNodeKey->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_debug_type($portalNodeKey));
        }

        $portalNodeId = $portalNodeKey->getUuid();
        $builder = $this->getQueryBuilder()->setParameter('portalNodeId', Id::toBinary($portalNodeId), Types::BINARY);
        $result = new PortalExtensionFindResult();

        /** @var array{class_name: string, active: string} $extension */
        foreach ($builder->iterateRows('portal_node_extension.id') as $extension) {
            $result->add(new UnsafeClassString($extension['class_name']), (bool) $extension['active']);
        }

        return $result;
    }

    private function getQueryBuilder(): SelectQueryBuilder
    {
        if (!$this->queryBuilder instanceof SelectQueryBuilder) {
            $this->queryBuilder = $this->queryFactory->createSelectBuilder(self::LOOKUP_QUERY);
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
