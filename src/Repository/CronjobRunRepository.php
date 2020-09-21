<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobRunInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobRunKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\CronjobRunRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobRunCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobRunStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CronjobRunRepository extends CronjobRunRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private EntityRepositoryInterface $cronjobs;

    private EntityRepositoryInterface $cronjobRuns;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        EntityRepositoryInterface $cronjobs,
        EntityRepositoryInterface $cronjobRuns,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->cronjobs = $cronjobs;
        $this->cronjobRuns = $cronjobRuns;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(CronjobKeyInterface $cronjobKey, \DateTimeInterface $queuedFor): CronjobRunKeyInterface
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $context = Context::createDefaultContext();
        $id = $this->storageKeyGenerator->generateKey(CronjobRunKeyInterface::class);

        if (!$id instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($id));
        }

        /** @var CronjobCollection $cronjobs */
        $cronjobs = $this->cronjobs->search(new Criteria([$cronjobKey->getUuid()]), $context)->getEntities();
        $first = $cronjobs->first();

        if (!$first instanceof CronjobEntity) {
            throw new NotFoundException();
        }

        $this->cronjobRuns->create([[
            'id' => $id->getUuid(),
            'cronjobId' => $first->getId(),
            'handler' => $first->getHandler(),
            'payload' => $first->getPayload(),
            'queuedFor' => $queuedFor,
            'portalNodeId' => $first->getPortalNodeId(),
        ]], $context);

        return $id;
    }

    public function listExecutables(\DateTimeInterface $now): iterable
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));
        $criteria->addFilter(
            new EqualsFilter('startedAt', null),
            new EqualsFilter('throwableClass', null),
            new RangeFilter('queuedFor', [
                RangeFilter::GTE => $now->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ])
        );

        $iterator = new RepositoryIterator($this->cronjobRuns, $context, $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new CronjobRunStorageKey($id);
            }
        }
    }

    /**
     * @throws UnsupportedStorageKeyException
     */
    public function read(CronjobRunKeyInterface $cronjobRunKey): CronjobRunInterface
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $context = Context::createDefaultContext();
        /** @var CronjobRunCollection $cronjobRuns */
        $cronjobRuns = $this->cronjobRuns->search(new Criteria([$cronjobRunKey->getUuid()]), $context)->getEntities();

        return $cronjobRuns->first();
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateStartedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $context = Context::createDefaultContext();
        $this->throwNotFoundWhenNoMatch($this->cronjobRuns, ['id' => $cronjobRunKey->getUuid()], $context);
        $this->throwNotFoundWhenNoChange($this->cronjobRuns->update([[
            'id' => $cronjobRunKey->getUuid(),
            'startedAt' => $now,
        ]], $context));
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateFinishedAt(CronjobRunKeyInterface $cronjobRunKey, \DateTimeInterface $now): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $context = Context::createDefaultContext();
        $this->throwNotFoundWhenNoMatch($this->cronjobRuns, ['id' => $cronjobRunKey->getUuid()], $context);
        $this->throwNotFoundWhenNoChange($this->cronjobRuns->update([[
            'id' => $cronjobRunKey->getUuid(),
            'finishedAt' => $now,
        ]], $context));
    }

    /**
     * @throws NotFoundException
     * @throws UnsupportedStorageKeyException
     */
    public function updateFailReason(CronjobRunKeyInterface $cronjobRunKey, \Throwable $throwable): void
    {
        if (!$cronjobRunKey instanceof CronjobRunStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobRunKey));
        }

        $serialize = null;

        try {
            $serialize = \serialize($throwable);
        } catch (\Throwable $ignored) {
        }

        $context = Context::createDefaultContext();
        $this->throwNotFoundWhenNoMatch($this->cronjobRuns, ['id' => $cronjobRunKey->getUuid()], $context);
        $this->throwNotFoundWhenNoChange($this->cronjobRuns->update([[
            'id' => $cronjobRunKey->getUuid(),
            'throwableClass' => \get_class($throwable),
            'throwableMessage' => $throwable->getMessage(),
            'throwableSerialized' => $serialize,
            'throwableFile' => $throwable->getFile(),
            'throwableLine' => $throwable->getLine(),
        ]], $context));
    }
}
