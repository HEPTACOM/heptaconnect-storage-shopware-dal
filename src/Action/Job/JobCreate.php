<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreateResults;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\Job\JobCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\JobTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Enum\JobStateEnum;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

final class JobCreate implements JobCreateActionInterface
{
    public const PAYLOAD_LOOKUP_QUERY = 'b2234327-93a0-4854-ac52-fba75f71da74';

    private const FORMAT_SERIALIZED_GZPRESS = 'serialized+gzpress';

    private Connection $connection;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private JobTypeAccessor $jobTypes;

    private EntityTypeAccessor $entityTypes;

    private QueryFactory $queryFactory;

    public function __construct(
        Connection $connection,
        StorageKeyGeneratorContract $storageKeyGenerator,
        JobTypeAccessor $jobTypes,
        EntityTypeAccessor $entityTypes,
        QueryFactory $queryFactory
    ) {
        $this->connection = $connection;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->jobTypes = $jobTypes;
        $this->entityTypes = $entityTypes;
        $this->queryFactory = $queryFactory;
    }

    public function create(JobCreatePayloads $payloads): JobCreateResults
    {
        $jobTypes = [];
        $entityTypes = [];
        $jobPayloads = [];

        /** @var JobCreatePayload $payload */
        foreach ($payloads as $payloadId => $payload) {
            $jobTypes[] = $payload->getJobType();
            $entityTypes[] = $payload->getMapping()->getEntityType();
            $portalNodeKey = $payload->getMapping()->getPortalNodeKey()->withoutAlias();
            $jobPayload = $payload->getJobPayload();

            if ($jobPayload !== null) {
                $serialized = \serialize($jobPayload);
                $jobPayloads[$payloadId] = [
                    'serialized' => $serialized,
                    'checksum' => \md5($serialized),
                ];
            }

            if (!($portalNodeKey instanceof PortalNodeStorageKey)) {
                throw new InvalidCreatePayloadException($payload, 1639268730, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
            }
        }

        $jobTypeIds = $this->jobTypes->getIdsForTypes($jobTypes);
        $entityTypeIds = $this->entityTypes->getIdsForTypes($entityTypes);

        foreach ($jobTypes as $jobType) {
            if (!\array_key_exists($jobType, $jobTypeIds)) {
                /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getJobType() === $jobType) {
                        throw new InvalidCreatePayloadException($payload, 1639268731);
                    }
                }
            }
        }

        foreach ($entityTypes as $entityType) {
            if (!\array_key_exists($entityType, $entityTypeIds)) {
                /** @var \Heptacom\HeptaConnect\Storage\Base\Action\Job\Create\JobCreatePayload $payload */
                foreach ($payloads as $payload) {
                    if ($payload->getMapping()->getEntityType() === $entityType) {
                        throw new InvalidCreatePayloadException($payload, 1639268732);
                    }
                }
            }
        }

        $jobPayloadChecksumIds = $this->getPayloadIds(\array_column($jobPayloads, 'checksum'));
        $result = new JobCreateResults();

        $this->connection->transactional(function () use ($payloads, $result, $entityTypeIds, $jobTypeIds, $jobPayloads, $jobPayloadChecksumIds): void {
            $keys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(JobKeyInterface::class, $payloads->count())));
            $now = DateTime::nowToStorage();
            $jobInserts = [];
            $payloadInserts = [];

            /** @var JobCreatePayload $payload */
            foreach ($payloads as $payloadId => $payload) {
                $jobTypeId = $jobTypeIds[$payload->getJobType()];
                $entityTypeId = $entityTypeIds[$payload->getMapping()->getEntityType()];
                /** @var PortalNodeStorageKey $portalNodeKey */
                $portalNodeKey = $payload->getMapping()->getPortalNodeKey()->withoutAlias();

                $key = $keys->current();
                $keys->next();

                if (!$key instanceof JobStorageKey) {
                    throw new InvalidCreatePayloadException($payload, 1639268733, new UnsupportedStorageKeyException(\get_class($key)));
                }

                $jobPayloadKey = null;
                $jobPayload = $payload->getJobPayload();
                $jobPayloadIndex = $jobPayloads[$payloadId] ?? null;

                if ($jobPayloadIndex !== null && $jobPayload !== null) {
                    $jobPayloadKey = $jobPayloadChecksumIds[$jobPayloadIndex['checksum']] ?? null;

                    if ($jobPayloadKey === null) {
                        $jobPayloadKey = Id::randomBinary();
                        $jobPayloadChecksumIds[$jobPayloadIndex['checksum']] = $jobPayloadKey;
                        $payloadInserts[] = [
                            'id' => $jobPayloadKey,
                            'checksum' => $jobPayloadIndex['checksum'],
                            'payload' => \gzcompress($jobPayloadIndex['serialized']),
                            'format' => self::FORMAT_SERIALIZED_GZPRESS,
                            'created_at' => $now,
                        ];
                    }
                }

                $jobInserts[] = [
                    'id' => Id::toBinary($key->getUuid()),
                    'external_id' => $payload->getMapping()->getExternalId(),
                    'portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
                    'entity_type_id' => Id::toBinary($entityTypeId),
                    'job_type_id' => Id::toBinary($jobTypeId),
                    'payload_id' => $jobPayloadKey,
                    'state_id' => JobStateEnum::open(),
                    'created_at' => $now,
                ];

                $result->push([new JobCreateResult($key)]);
            }

            try {
                $this->connection->transactional(function () use ($jobInserts, $payloadInserts): void {
                    // TODO batch
                    foreach ($payloadInserts as $insert) {
                        $this->connection->insert('heptaconnect_job_payload', $insert, [
                            'id' => Types::BINARY,
                        ]);
                    }

                    foreach ($jobInserts as $insert) {
                        $this->connection->insert('heptaconnect_job', $insert, [
                            'id' => Types::BINARY,
                            'portal_node_id' => Types::BINARY,
                            'entity_type_id' => Types::BINARY,
                            'job_type_id' => Types::BINARY,
                            'payload_id' => Types::BINARY,
                            'state_id' => Types::BINARY,
                        ]);
                    }
                });
            } catch (\Throwable $throwable) {
                throw new CreateException(1639268734, $throwable);
            }
        });

        return $result;
    }

    /**
     * @param string[] $checksums
     *
     * @return array<string, string>
     */
    private function getPayloadIds(array $checksums): array
    {
        $builder = $this->queryFactory->createBuilder(self::PAYLOAD_LOOKUP_QUERY);

        $builder
            ->from('heptaconnect_job_payload', 'job_payload')
            ->addOrderBy('job_payload.id')
            ->select([
                'job_payload.checksum checksum',
                'job_payload.id id',
            ])
            ->where($builder->expr()->in('job_payload.checksum', ':checksums'))
            ->setMaxResults(\count($checksums))
            ->setParameter('checksums', $checksums, Connection::PARAM_STR_ARRAY);
        $builder->setIsForUpdate(true);

        $rows = [];

        foreach ($builder->iterateRows() as $row) {
            $rows[$row['checksum']] = $row['id'];
        }

        return $rows;
    }
}
