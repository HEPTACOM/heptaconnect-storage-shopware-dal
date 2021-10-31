<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteOverviewResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteOverview implements RouteOverviewActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(RouteOverviewCriteria $criteria): iterable
    {
        // TODO cache built query
        $builder = $this->connection->createQueryBuilder();
        $builder
            ->from('heptaconnect_route', 'r')
            ->innerJoin(
                'r',
                'heptaconnect_entity_type',
                'e',
                $builder->expr()->eq('e.id', 'r.type_id')
            )
            ->innerJoin(
                'r',
                'heptaconnect_portal_node',
                's',
                $builder->expr()->eq('s.id', 'r.source_id')
            )
            ->innerJoin(
                'r',
                'heptaconnect_portal_node',
                't',
                $builder->expr()->eq('t.id', 'r.target_id')
            )
            ->select([
                'r.id id',
                'e.type e_t',
                's.id s_id',
                's.class_name s_cn',
                't.id t_id',
                't.class_name t_cn',
                'r.created_at ct',
            ])
            ->where($builder->expr()->isNull('r.deleted_at'));

        foreach ($criteria->getSort() as $field => $direction) {
            $dalDirection = $direction === RouteOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dalFieldName = null;

            switch ($field) {
                case RouteOverviewCriteria::FIELD_CREATED:
                    $dalFieldName = 'r.created_at';
                    break;
                case RouteOverviewCriteria::FIELD_ENTITY_TYPE:
                    $dalFieldName = 'e.type';
                    break;
                case RouteOverviewCriteria::FIELD_SOURCE:
                    // TODO allow sort by portal name
                    $dalFieldName = 's.class_name';
                    break;
                case RouteOverviewCriteria::FIELD_TARGET:
                    // TODO allow sort by portal name
                    $dalFieldName = 't.class_name';
                    break;
            }

            if ($dalFieldName === null) {
                continue;
            }

            $builder->addOrderBy($dalFieldName, $dalDirection);
        }

        $builder->addOrderBy('r.id', 'ASC');

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
            static fn (array $row): RouteOverviewResult => new RouteOverviewResult(
                new RouteStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
                (string) $row['e_t'],
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['s_id'])),
                (string) $row['s_cn'],
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['t_id'])),
                (string) $row['t_cn'],
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['ct'])
            )
        );
    }
}
