<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Start\JobStartPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Start\JobStartResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobStartActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\AbstractJobTransitionAction;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobStart extends AbstractJobTransitionAction implements JobStartActionInterface
{
    public const string UPDATE_QUERY = '0803daca-3ca7-44c4-a492-42cc51e46854';

    public const string FIND_QUERY = '1bbfc5fe-756c-4171-b645-ad2a6c10f4e7';

    private ?QueryBuilder $updateQueryBuilder = null;

    public function __construct(
        private readonly Connection $connection,
        protected readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function start(JobStartPayload $payload): JobStartResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobStartResult {
            [
                'affected' => $jobIds,
                'skipped' => $skippedJobIds,
            ] = $this->transition($payload, $connection, JobStateEnum::started(), self::FIND_QUERY);

            return new JobStartResult($jobIds, $skippedJobIds);
        });
    }

    private function getUpdateQueryBuilder(): QueryBuilder
    {
        if ($this->updateQueryBuilder instanceof QueryBuilder) {
            return $this->updateQueryBuilder;
        }

        $builder = $this->queryFactory->createBuilder(self::UPDATE_QUERY);
        $expr = $builder->expr();

        return $this->updateQueryBuilder = $builder->update('heptaconnect_job', 'job')
            ->set('job.state_id', ':stateId')
            ->set('job.transaction_id', ':transactionId')
            ->andWhere($expr->in('job.id', ':jobIds'))
            ->andWhere($expr->neq('job.state_id', ':stateId'))
            ->setParameter('stateId', JobStateEnum::started(), Types::BINARY);
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
