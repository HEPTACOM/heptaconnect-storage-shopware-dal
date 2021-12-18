<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Listing\JobListFinishedActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\Listing\JobListFinishedResult;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use Shopware\Core\Framework\Uuid\Uuid;

class JobFinishedList implements JobListFinishedActionInterface
{
    private ?QueryBuilder $builder = null;

    private Connection $connection;

    private QueryIterator $iterator;

    public function __construct(Connection $connection, QueryIterator $iterator)
    {
        $this->connection = $connection;
        $this->iterator = $iterator;
    }

    public function list(): iterable
    {
        yield from \iterable_map(
            \iterable_map(
                $this->iterator->iterateColumn($this->getBuilderCached()),
                [Uuid::class, 'fromBytesToHex']
            ),
            static fn (string $id) => new JobListFinishedResult(new JobStorageKey($id))
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
            ->from('heptaconnect_job', 'job')
            ->select(['job.id id'])
            ->where($builder->expr()->eq('job.state_id', ':finished'))
            ->addOrderBy('job.id')
            ->setParameter('finished', JobStateEnum::finished(), Types::BINARY);
    }
}
