<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCapabilityOverviewResult;
use Shopware\Core\Defaults;

class RouteCapabilityOverview implements RouteCapabilityOverviewActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(RouteCapabilityOverviewCriteria $criteria): iterable
    {
        // TODO cache built query
        $builder = $this->getBuilder();

        foreach ($criteria->getSort() as $field => $direction) {
            $dalDirection = $direction === RouteCapabilityOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dalFieldName = null;

            switch ($field) {
                case RouteCapabilityOverviewCriteria::FIELD_CREATED:
                    $dalFieldName = 'c.created_at';
                    break;
                case RouteCapabilityOverviewCriteria::FIELD_NAME:
                    $dalFieldName = 'c.name';
                    break;
            }

            if ($dalFieldName === null) {
                continue;
            }

            $builder->addOrderBy($dalFieldName, $dalDirection);
        }

        $builder->addOrderBy('c.id', 'ASC');

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
            static fn (array $row): RouteCapabilityOverviewResult => new RouteCapabilityOverviewResult(
                (string) $row['n'],
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['ct'])
            )
        );
    }

    protected function getBuilder(): QueryBuilder
    {
        $builder = $this->connection->createQueryBuilder();

        // TODO human readable
        return $builder
            ->from('heptaconnect_route_capability', 'c')
            ->select([
                'c.name n',
                'c.created_at ct',
            ])
            ->where($builder->expr()->isNull('c.deleted_at'));
    }
}
