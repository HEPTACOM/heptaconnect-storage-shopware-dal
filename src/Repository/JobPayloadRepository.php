<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\JobPayloadRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobPayloadEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\DalAccess;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class JobPayloadRepository extends JobPayloadRepositoryContract
{
    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED = 'serialized';

    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED_GZPRESS = 'serialized+gzpress';

    private EntityRepositoryInterface $jobPayloads;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ContextFactory $contextFactory;

    private DalAccess $dalAccess;

    public function __construct(
        EntityRepositoryInterface $jobPayloads,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ContextFactory $contextFactory,
        DalAccess $dalAccess
    ) {
        $this->jobPayloads = $jobPayloads;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->contextFactory = $contextFactory;
        $this->dalAccess = $dalAccess;
    }

    public function add(array $payloads): array
    {
        $context = $this->contextFactory->create();
        $creates = [];
        $result = [];
        $checksums = [];

        foreach ($payloads as $payloadKey => $payload) {
            $serialize = \serialize($payload);
            $checksum = \md5($serialize);

            $checksums[$payloadKey] = $checksum;
            $creates[$checksum] = [
                'payload' => \gzcompress($serialize),
                'checksum' => $checksum,
                'format' => self::FORMAT_SERIALIZED_GZPRESS,
            ];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('checksum', \array_values($checksums)));
        $existingChecksums = $this->dalAccess->queryValueById($this->jobPayloads, 'checksum', $criteria, $context);

        foreach ($checksums as $payloadKey => $checksum) {
            if (($key = \array_search($checksum, $existingChecksums)) !== false) {
                $result[$payloadKey] = new JobPayloadStorageKey($key);
                unset($creates[$checksum]);
            }
        }

        if ($creates !== []) {
            $keys = \iterable_to_array($this->storageKeyGenerator->generateKeys(JobPayloadKeyInterface::class, \count($creates)));

            foreach ($creates as $checksum => &$create) {
                $key = \array_shift($keys);

                if (!$key instanceof JobPayloadStorageKey) {
                    throw new UnsupportedStorageKeyException(\get_class($key));
                }

                foreach (\array_keys($checksums, $checksum) as $payloadKey) {
                    $result[$payloadKey] = $key;
                }

                $create['id'] = $key->getUuid();
            }

            $this->jobPayloads->create(\array_values($creates), $context);
        }

        return $result;
    }

    public function remove(JobPayloadKeyInterface $processPayloadKey): void
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$processPayloadKey->getUuid()]);
        $criteria->setLimit(1);
        $searchResult = $this->jobPayloads->searchIds($criteria, $context);
        $storageId = $searchResult->firstId();

        if (\is_null($storageId)) {
            throw new NotFoundException();
        }

        $this->jobPayloads->delete([[
            'id' => $storageId,
        ]], $context);
    }

    public function list(): iterable
    {
        $context = $this->contextFactory->create();
        $criteria = (new Criteria())->setLimit(50);
        $iterator = new RepositoryIterator($this->jobPayloads, $context, $criteria);

        while (!\is_null($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new JobPayloadStorageKey($id);
            }
        }
    }

    public function has(JobPayloadKeyInterface $processPayloadKey): bool
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$processPayloadKey->getUuid()]);
        $criteria->setLimit(1);
        $searchResult = $this->jobPayloads->searchIds($criteria, $context);
        $storageId = $searchResult->firstId();

        return !\is_null($storageId);
    }

    public function get(JobPayloadKeyInterface $processPayloadKey): array
    {
        if (!$processPayloadKey instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($processPayloadKey));
        }

        $context = $this->contextFactory->create();
        $criteria = new Criteria([$processPayloadKey->getUuid()]);
        $criteria->setLimit(1);
        $entity = $this->jobPayloads->search($criteria, $context)->first();

        if (!$entity instanceof JobPayloadEntity) {
            throw new NotFoundException();
        }

        if ($entity->getFormat() === self::FORMAT_SERIALIZED) {
            return (array) \unserialize($entity->getPayload());
        }

        if ($entity->getFormat() === self::FORMAT_SERIALIZED_GZPRESS) {
            return (array) \unserialize(\gzuncompress($entity->getPayload()));
        }

        return (array) $entity->getPayload();
    }

    public function cleanup(): void
    {
        $context = $this->contextFactory->create();
        $criteria = (new Criteria())->addFilter(
            new EqualsFilter('jobs.id', null)
        );
        $iterator = new RepositoryIterator($this->jobPayloads, $context, $criteria);
        while (($orphanedJobIds = $iterator->fetchIds()) !== null) {
            $this->jobPayloads->delete(\array_map(
                static fn (string $orphanedJobId): array => ['id' => $orphanedJobId],
                $orphanedJobIds
            ), $context);
            $criteria->setOffset(0);
        }
    }
}
