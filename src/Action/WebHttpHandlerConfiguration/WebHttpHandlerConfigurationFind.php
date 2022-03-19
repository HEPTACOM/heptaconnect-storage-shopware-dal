<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration;

use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathIdResolver;

class WebHttpHandlerConfigurationFind implements WebHttpHandlerConfigurationFindActionInterface
{
    public const LOOKUP_QUERY = 'f6c5db7b-004d-40c8-b9cc-53707aab658b';

    private ?QueryBuilder $builder = null;

    private QueryFactory $queryFactory;

    private WebHttpHandlerPathIdResolver $pathIdResolver;

    public function __construct(QueryFactory $queryFactory, WebHttpHandlerPathIdResolver $pathIdResolver)
    {
        $this->queryFactory = $queryFactory;
        $this->pathIdResolver = $pathIdResolver;
    }

    public function find(WebHttpHandlerConfigurationFindCriteria $criteria): WebHttpHandlerConfigurationFindResult
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $builder = $this->getBuilderCached();
        $builder->setParameter(':key', $criteria->getConfigurationKey());
        $builder->setParameter(':pathId', Id::toBinary($this->pathIdResolver->getIdFromPath($criteria->getPath())), Type::BINARY);
        $builder->setParameter(':portalNodeKey', Id::toBinary($portalNodeKey->getUuid()), Type::BINARY);

        /** @var array{type: string, value: string}|null $row */
        $row = $builder->fetchSingleRow();

        if ($row === null) {
            return new WebHttpHandlerConfigurationFindResult(null);
        }

        $value = null;

        switch ($row['type']) {
            case 'serialized':
            default:
                $preValue = \unserialize((string) $row['value']);

                if (\is_array($preValue)) {
                    $value = $preValue;
                }

                break;
        }

        return new WebHttpHandlerConfigurationFindResult(\is_array($value) ? $value : null);
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
        $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        return $builder
            ->from('heptaconnect_web_http_handler_configuration', 'config')
            ->innerJoin(
                'config',
                'heptaconnect_web_http_handler',
                'handler',
                $builder->expr()->eq('config.handler_id', 'handler.id')
            )
            ->where(
                $builder->expr()->eq('config.key', ':key'),
                $builder->expr()->eq('handler.path_id', ':pathId'),
                $builder->expr()->eq('handler.portal_node_id', ':portalNodeKey')
            )
            ->select([
                'config.type type',
                'config.value value',
            ]);
    }
}
