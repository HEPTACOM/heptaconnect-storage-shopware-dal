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
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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

    public function __construct(
        EntityRepositoryInterface $jobPayloads,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ContextFactory $contextFactory
    ) {
        $this->jobPayloads = $jobPayloads;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->contextFactory = $contextFactory;
    }

    public function add(array $payload): JobPayloadKeyInterface
    {
        $context = $this->contextFactory->create();
        $serialize = \serialize($payload);
        $checksum = \md5($serialize);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('checksum', $checksum));
        $criteria->setLimit(1);
        $existingId = $this->jobPayloads->searchIds($criteria, $context)->firstId();

        if ($existingId !== null) {
            return new JobPayloadStorageKey($existingId);
        }

        $key = $this->storageKeyGenerator->generateKey(JobPayloadKeyInterface::class);

        if (!$key instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->jobPayloads->create([[
            'id' => $key->getUuid(),
            'payload' => \gzcompress($serialize),
            'checksum' => $checksum,
            'format' => self::FORMAT_SERIALIZED_GZPRESS,
        ]], $context);

        return $key;
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
}
