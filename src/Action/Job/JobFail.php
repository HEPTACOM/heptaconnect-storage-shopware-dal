<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Fail\JobFailPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Fail\JobFailResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFailActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\AbstractJobTransitionAction;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final readonly class JobFail extends AbstractJobTransitionAction implements JobFailActionInterface
{
    public const string UPDATE_QUERY = '2d59f1a4-4baf-4cda-b762-16fb5beda452';

    public const string FIND_QUERY = '9b00334a-cc0b-4017-a9dc-e2520a872064';

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory,
    ) {
        parent::__construct($this->queryFactory);
    }

    #[\Override]
    public function fail(JobFailPayload $payload): JobFailResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFailResult {
            [
                'affected' => $jobIds,
                'skipped' => $skippedJobIds,
            ] = $this->transition($payload, $connection, JobStateEnum::failed(), self::FIND_QUERY);

            return new JobFailResult($jobIds, $skippedJobIds);
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
            ->andWhere($expr->eq('job.state_id', ':oldStateId'))
            ->setParameter('newStateId', JobStateEnum::failed(), Types::BINARY)
            ->setParameter('oldStateId', JobStateEnum::started(), Types::BINARY);
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
