<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Overview\PortalNodeOverviewActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Overview\PortalNodeOverviewCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Overview\PortalNodeOverviewResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidOverviewCriteriaException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;

class PortalNodeOverview implements PortalNodeOverviewActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function overview(PortalNodeOverviewCriteria $criteria): iterable
    {
        $builder = $this->getBuilderCached();
        $classNameFilter = $criteria->getClassNameFilter();

        if ($classNameFilter !== []) {
            $builder->andWhere($builder->expr()->in('portal_node.class_name', ':classNames'));
            $builder->setParameter('classNames', $classNameFilter, Connection::PARAM_STR_ARRAY);
        }

        foreach ($criteria->getSort() as $field => $direction) {
            $dbalDirection = $direction === PortalNodeOverviewCriteria::SORT_ASC ? 'ASC' : 'DESC';
            $dbalFieldName = null;

            switch ($field) {
                case PortalNodeOverviewCriteria::FIELD_CREATED:
                    $dbalFieldName = 'portal_node.created_at';
                    break;
                case PortalNodeOverviewCriteria::FIELD_CLASS_NAME:
                    $dbalFieldName = 'portal_node.class_name';
                    break;
            }

            if ($dbalFieldName === null) {
                throw new InvalidOverviewCriteriaException($criteria, 1640405544);
            }

            $builder->addOrderBy($dbalFieldName, $dbalDirection);
        }

        $builder->addOrderBy('portal_node.id', 'ASC');

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
            throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1640405545);
        }

        yield from \iterable_map(
            $statement->fetchAll(FetchMode::ASSOCIATIVE),
            static fn (array $row): PortalNodeOverviewResult => new PortalNodeOverviewResult(
                new PortalNodeStorageKey(Uuid::fromBytesToHex((string) $row['id'])),
                /* @phpstan-ignore-next-line */
                (string) $row['portal_node_class_name'],
                /* @phpstan-ignore-next-line */
                \date_create_immutable_from_format(Defaults::STORAGE_DATE_TIME_FORMAT, (string) $row['created_at']),
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
            ->from('heptaconnect_portal_node', 'portal_node')
            ->select([
                'portal_node.id id',
                'portal_node.class_name portal_node_class_name',
                'portal_node.created_at created_at',
            ])
            ->where($builder->expr()->isNull('portal_node.deleted_at'));
    }
}
