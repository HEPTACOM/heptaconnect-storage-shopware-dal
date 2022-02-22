<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNodeAlias;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Overview\PortalNodeAliasOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNodeAlias\Overview\PortalNodeAliasOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNodeAlias\PortalNodeAliasOverviewActionInterface;

class PortalNodeAliasOverview implements PortalNodeAliasOverviewActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(PortalNodeAliasOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult($page * $pageSize);
            }
        }

        $statement = $builder->execute();

        if (!$statement instanceof ResultStatement) {
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1645459168);
        }

        yield from \iterable_map(
            $statement->fetchAllAssociative(),
            static fn (array $row): PortalNodeAliasOverviewResult => new PortalNodeAliasOverviewResult('PortalNode:' . \bin2hex($row['id']), $row['alias']),
        );
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
                'portal_node.alias alias',
            ])
            ->where($builder->expr()->isNotNull('alias'));
    }
}
