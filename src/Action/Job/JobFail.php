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
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobFail extends AbstractJobTransitionAction implements JobFailActionInterface
{
    public const string UPDATE_QUERY = '2d59f1a4-4baf-4cda-b762-16fb5beda452';

    public const string FIND_QUERY = '9b00334a-cc0b-4017-a9dc-e2520a872064';

    private ?QueryBuilder $updateQueryBuilder = null;

    public function __construct(
        private readonly Connection $connection,
        protected readonly QueryFactory $queryFactory,
    ) {
    }

    #[\Override]
    public function fail(JobFailPayload $payload): JobFailResult
    {
        return $this->connection->transactional(function (Connection $connection) use ($payload): JobFailResult {
            $jobIds = $this->getJobIds($payload->getJobKeys());
            $createdAt = DateTime::toStorage($payload->getCreatedAt());
            $message = $payload->getMessage();
            $transactionId = Id::randomBinary();

            $affected = $this->updateAndCollectNumberAffected($jobIds, $transactionId);

            if ($affected < \count($jobIds)) {
                $affectedJobIds = \iterable_to_array(
                    $this->getSelectQueryBuilder(self::FIND_QUERY)->setParameter('transactionId', $transactionId)->iterateColumn()
                );
                $skippedJobIds = \array_diff($jobIds, $affectedJobIds);
                $jobIds = $affectedJobIds;
            } else {
                $skippedJobIds = [];
            }

            foreach ($jobIds as $jobId) {
                $connection->insert('heptaconnect_job_history', [
                    'id' => Id::randomBinary(),
                    'job_id' => $jobId,
                    'state_id' => JobStateEnum::failed(),
                    'message' => $message,
                    'created_at' => $createdAt,
                ], [
                    'id' => Types::BINARY,
                    'job_id' => Types::BINARY,
                    'state_id' => Types::BINARY,
                ]);
            }

            return new JobFailResult($this->packJobKeys($jobIds), $this->packJobKeys($skippedJobIds));
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
