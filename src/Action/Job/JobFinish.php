<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Finish\JobFinishResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobFinishActionInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\AbstractJobTransitionAction;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final readonly class JobFinish extends AbstractJobTransitionAction implements JobFinishActionInterface
{
    public const string UPDATE_QUERY = '393a0ae1-5f42-4a49-96a3-9a23c26e6bd2';

    public const string FIND_QUERY = '84e5495d-4733-4e8a-b775-aafba23daa8c';

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory,
    ) {
        parent::__construct($this->queryFactory);
    }

    #[\Override]
    public function finish(JobFinishPayload $payload): JobFinishResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFinishResult {
            [
                'affected' => $jobIds,
                'skipped' => $skippedJobIds,
            ] = $this->transition($payload, $connection, JobStateEnum::finished(), self::FIND_QUERY);

            return new JobFinishResult($jobIds, $skippedJobIds);
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
            ->setParameter('newStateId', JobStateEnum::finished(), Types::BINARY)
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
