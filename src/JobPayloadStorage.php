<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job\JobPayloadEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobPayloadStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class JobPayloadStorage extends JobPayloadStorageContract
{
    /**
     * @deprecated TODO remove serialized format
     */
    private const FORMAT_SERIALIZED = 'serialized';

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

    public function add(object $payload): JobPayloadKeyInterface
    {
        $key = $this->storageKeyGenerator->generateKey(JobPayloadKeyInterface::class);

        if (!$key instanceof JobPayloadStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();
        $serialize = \serialize($payload);

        $this->jobPayloads->create([[
            'id' => $key->getUuid(),
            'payload' => $serialize,
            'checksum' => \md5($serialize),
            'format' => self::FORMAT_SERIALIZED,
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

        while (!empty($ids = $iterator->fetchIds())) {
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

    public function get(JobPayloadKeyInterface $processPayloadKey): object
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
            return (object) \unserialize($entity->getPayload());
        }

        return (object) $entity->getPayload();
    }
}
