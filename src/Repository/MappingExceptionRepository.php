<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingExceptionKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\MappingExceptionRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingExceptionStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class MappingExceptionRepository extends MappingExceptionRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private EntityRepositoryInterface $mappingExceptions;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ContextFactory $contextFactory;

    public function __construct(
        EntityRepositoryInterface $mappingExceptions,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ContextFactory $contextFactory
    ) {
        $this->mappingExceptions = $mappingExceptions;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->contextFactory = $contextFactory;
    }

    public function create(MappingKeyInterface $mappingKey, \Throwable $throwable): MappingExceptionKeyInterface
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        $insert = [];
        $resultKey = null;
        $previousKey = null;

        foreach (self::unwrapException($throwable) as $exceptionItem) {
            $key = $this->storageKeyGenerator->generateKey(MappingExceptionKeyInterface::class);

            if (!$key instanceof MappingExceptionStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $resultKey ??= $key;

            $insert[] = [
                'id' => $key->getUuid(),
                'previousId' => $previousKey ? $previousKey->getUuid() : null,
                'groupPreviousId' => $resultKey && !$key->equals($resultKey) ? $resultKey->getUuid() : null,
                'mappingId' => $mappingKey->getUuid(),
                'type' => \get_class($exceptionItem),
                'message' => $exceptionItem->getMessage(),
                'stackTrace' => \json_encode($exceptionItem->getTrace()),
            ];
            $previousKey = $key;
        }

        $this->mappingExceptions->create($insert, $this->contextFactory->create());

        return $resultKey;
    }

    public function listByMapping(MappingKeyInterface $mappingKey): iterable
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addFilter(new EqualsFilter('mappingId', $mappingKey->getUuid()));
        $iterator = new RepositoryIterator($this->mappingExceptions, $this->contextFactory->create(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new MappingExceptionStorageKey($id);
            }
        }
    }

    public function listByMappingAndType(MappingKeyInterface $mappingKey, string $type): iterable
    {
        if (!$mappingKey instanceof MappingStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($mappingKey));
        }

        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addFilter(
            new EqualsFilter('mappingId', $mappingKey->getUuid()),
            new EqualsFilter('type', $type),
        );
        $iterator = new RepositoryIterator($this->mappingExceptions, $this->contextFactory->create(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new MappingExceptionStorageKey($id);
            }
        }
    }

    public function delete(MappingExceptionKeyInterface $key): void
    {
        if (!$key instanceof MappingExceptionStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $context = $this->contextFactory->create();
        $this->throwNotFoundWhenNoMatch($this->mappingExceptions, $key->getUuid(), $context);
        $this->throwNotFoundWhenNoChange($this->mappingExceptions->delete([[
            'id' => $key->getUuid(),
        ]], $context));
    }

    /**
     * @psalm-return array<array-key, \Throwable>
     */
    private static function unwrapException(\Throwable $exception): array
    {
        $exceptions = [$exception];

        while (($exception = $exception->getPrevious()) instanceof \Throwable) {
            $exceptions[] = $exception;
        }

        return $exceptions;
    }
}
