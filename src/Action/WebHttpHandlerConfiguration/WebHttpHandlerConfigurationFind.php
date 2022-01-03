<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Find\WebHttpHandlerConfigurationFindActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\WebHttpHandlerPathIdResolver;

class WebHttpHandlerConfigurationFind implements WebHttpHandlerConfigurationFindActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    private WebHttpHandlerPathIdResolver $pathIdResolver;

    public function __construct(Connection $connection, WebHttpHandlerPathIdResolver $pathIdResolver)
    {
        $this->connection = $connection;
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
        $builder->setParameter(':pathId', \hex2bin($this->pathIdResolver->getIdFromPath($criteria->getPath())), Type::BINARY);
        $builder->setParameter(':portalNodeKey', \hex2bin($portalNodeKey->getUuid()), Type::BINARY);

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1637542091);
        }

        $row = $statement->fetch(FetchMode::ASSOCIATIVE);

        if ($row === false) {
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
        $builder = new QueryBuilder($this->connection);

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
