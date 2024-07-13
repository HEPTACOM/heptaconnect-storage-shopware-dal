<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Schedule\JobSchedulePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Schedule\JobScheduleResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobScheduleActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\AbstractJobTransitionAction;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobSchedule extends AbstractJobTransitionAction implements JobScheduleActionInterface
{
    public const string UPDATE_QUERY = '72372e2f-6e02-470b-89d5-b65ee88024b5';

    public const string FIND_QUERY = '87c10b4f-3dcd-460d-ba04-b38acbad6cbe';

    public function __construct(
        private readonly Connection $connection,
        protected readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function schedule(JobSchedulePayload $payload): JobScheduleResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobScheduleResult {
            [
                'affected' => $jobIds,
                'skipped' => $skippedJobIds,
            ] = $this->transition($payload, $connection, JobStateEnum::open(), self::FIND_QUERY);

            return new JobScheduleResult($jobIds, $skippedJobIds);
        });
    }

    private function getUpdateQueryBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::UPDATE_QUERY);
        $expr = $builder->expr();

        return $builder->update('heptaconnect_job', 'job')
            ->set('job.state_id', ':newStateId')
            ->set('job.transaction_id', ':transactionId')
            ->andWhere($expr->in('job.id', ':jobIds'))
            ->andWhere($expr->in('job.state_id', ':oldStateIds'))
            ->setParameter('newStateId', JobStateEnum::open(), Types::BINARY)
            ->setParameter('oldStateIds', [
                JobStateEnum::failed(),
                JobStateEnum::finished(),
            ], ArrayParameterType::STRING);
    }

    #[\Override]
    protected function updateAndCollectNumberAffected(array $jobIds, string $transactionId): int
    {
        return $this->getUpdateQueryBuilder()
            ->setParameter('jobIds', $jobIds, ArrayParameterType::STRING)
            ->setParameter('transactionId', $transactionId, Types::BINARY)
            ->executeStatement();
    }
}
