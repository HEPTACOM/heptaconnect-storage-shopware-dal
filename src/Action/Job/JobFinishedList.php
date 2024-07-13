<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Listing\JobListFinishedResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobListFinishedActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final class JobFinishedList implements JobListFinishedActionInterface
{
    public const string LIST_QUERY = '008ced6c-7517-46f8-a8a0-8f3c31b50467';

    public function __construct(
        private readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function list(): iterable
    {
        foreach (Id::toHexIterable($this->getBuilder()->iterateColumn('job.id')) as $id) {
            yield new JobListFinishedResult(new JobStorageKey($id));
        }
    }

    private function getBuilder(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::LIST_QUERY);

        return $builder
            ->from('heptaconnect_job', 'job')
            ->select(['job.id id'])
            ->where($builder->expr()->eq('job.state_id', ':finished'))
            ->setParameter('finished', JobStateEnum::finished(), Types::BINARY);
    }
}
