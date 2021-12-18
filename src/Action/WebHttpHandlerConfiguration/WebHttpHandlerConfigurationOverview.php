<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\WebHttpHandlerConfiguration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Overview\WebHttpHandlerConfigurationOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Overview\WebHttpHandlerConfigurationOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\WebHttpHandlerConfiguration\Overview\WebHttpHandlerConfigurationOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Shopware\Core\Framework\Uuid\Uuid;
/*
class WebHttpHandlerConfigurationOverview implements WebHttpHandlerConfigurationOverviewActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(WebHttpHandlerConfigurationOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === WebHttpHandlerConfigurationOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case WebHttpHandlerConfigurationOverviewCriteria::FIELD_PATH:
                    $dbalFieldName = 'path.path';
                    break;
                case WebHttpHandlerConfigurationOverviewCriteria::FIELD_PORTAL_NODE:
                    $dbalFieldName = 'handler.portal_node_id';
                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1636816616);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('handler.id', 'ASC');

        $pageSize = $criteria->getPageSize();

        if ($pageSize !== null && $pageSize > 0) {
            $page = $criteria->getPage();

            $builder->setMaxResults($pageSize);

            if ($page > 0) {
                $builder->setFirstResult($page * $pageSize);
            }
        }

        yield from \iterable_map(
            $builder->execute()->fetchAll(FetchMode::ASSOCIATIVE),
            static fn (array $row): WebHttpHandlerConfigurationOverviewResult => new WebHttpHandlerConfigurationOverviewResult(
                (string) $row['path'],
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['portal_node_id']))
            )
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
            ->from('heptaconnect_web_http_handler', 'handler')
            ->innerJoin(
                'handler',
                'heptaconnect_web_http_handler_path',
                'path',
                $builder->expr()->eq('path.id', 'handler.path_id')
            )
            ->select([
                'path.path path',
                'handler.portal_node_id portal_node_id',
            ]);
    }
}
*/
