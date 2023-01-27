<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Listing\JobListFinishedResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobListFinishedActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

final class JobFinishedList implements JobListFinishedActionInterface
{
    public const LIST_QUERY = '008ced6c-7517-46f8-a8a0-8f3c31b50467';

    private ?QueryBuilder $builder = null;

    public function __construct(
        private QueryFactory $queryFactory,
        private QueryIterator $iterator
    ) {
    }

    public function list(): iterable
    {
        return \iterable_map(
            Id::toHexIterable($this->iterator->iterateColumn($this->getBuilderCached())),
            static fn (string $id) => new JobListFinishedResult(new JobStorageKey($id))
        );
    }

    private function getBuilderCached(): QueryBuilder
    {
        if (!$this->builder instanceof QueryBuilder) {
            $this->builder = $this->getBuilder();
            $this->builder->setFirstResult(0);
            $this->builder->setMaxResults(null);
            $this->builder->getSQL();
        }

        return clone $this->builder;
    }

    private function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::LIST_QUERY);

        return $builder
            ->from('heptaconnect_job', 'job')
            ->select(['job.id id'])
            ->where($builder->expr()->eq('job.state_id', ':finished'))
            ->addOrderBy('job.id')
            ->setParameter('finished', JobStateEnum::finished(), Types::BINARY);
    }
}
