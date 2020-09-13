<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\Cronjob\Contract\CronjobInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\CronjobRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob\CronjobEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class CronjobRepository extends CronjobRepositoryContract
{
    private EntityRepositoryInterface $cronjobs;

    private StorageKeyGeneratorContract $keyGenerator;

    public function __construct(EntityRepositoryInterface $cronjobs, StorageKeyGeneratorContract $keyGenerator)
    {
        $this->cronjobs = $cronjobs;
        $this->keyGenerator = $keyGenerator;
    }

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        string $cronExpression,
        string $handler,
        \DateTimeInterface $nextExecution,
        ?array $payload = null
    ): CronjobInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = Context::createDefaultContext();
        $key = $this->keyGenerator->generateKey(CronjobKeyInterface::class);

        $this->cronjobs->create([[
            'id' => $key->getUuid(),
            'cronExpression' => $cronExpression,
            'handler' => $handler,
            'payload' => $payload,
            'queuedUntil' => $nextExecution,
            'portalNodeId' => $portalNodeKey->getUuid(),
        ]], $context);

        /** @var CronjobCollection $cronjobs */
        $cronjobs = $this->cronjobs->search(new Criteria([$key->getUuid()]), $context)->getEntities();
        /** @var CronjobEntity $first */
        $first = $cronjobs->first();

        return $first;
    }

    public function read(CronjobKeyInterface $cronjobKey): CronjobInterface
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $context = Context::createDefaultContext();
        /** @var CronjobCollection $cronjobs */
        $cronjobs = $this->cronjobs->search(new Criteria([$cronjobKey->getUuid()]), $context)->getEntities();

        $cronjob = $cronjobs->first();

        if (!$cronjob instanceof CronjobEntity) {
            throw new NotFoundException();
        }

        return $cronjob;
    }

    public function updateNextExecutionTime(CronjobKeyInterface $cronjobKey, \DateTimeInterface $nextExecution): void
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $context = Context::createDefaultContext();
        $cronjobIds = $this->cronjobs->searchIds(new Criteria([$cronjobKey->getUuid()]), $context);

        if ($cronjobIds->getTotal() <= 0) {
            throw new NotFoundException();
        }

        $this->cronjobs->update([[
            'id' => $cronjobKey->getUuid(),
            'queuedUntil' => $nextExecution,
        ]], $context);
    }

    public function delete(CronjobKeyInterface $cronjobKey): void
    {
        if (!$cronjobKey instanceof CronjobStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($cronjobKey));
        }

        $context = Context::createDefaultContext();
        $cronjobIds = $this->cronjobs->searchIds(new Criteria([$cronjobKey->getUuid()]), $context);

        if ($cronjobIds->getTotal() <= 0) {
            throw new NotFoundException();
        }

        $this->cronjobs->delete([[
            'id' => $cronjobKey,
        ]], $context);
    }

    public function listExecutables(?\DateTimeInterface $until = null): iterable
    {
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->setLimit(50);
        $criteria->addSorting(
            new FieldSorting('queuedUntil', FieldSorting::DESCENDING),
            new FieldSorting('createdAt', FieldSorting::ASCENDING)
        );

        if ($until instanceof \DateTimeInterface) {
            $criteria->addFilter(new RangeFilter('queuedUntil', [
                RangeFilter::LTE => $until->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]));
        }

        $iterator = new RepositoryIterator($this->cronjobs, $context, $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new CronjobStorageKey($id);
            }
        }
    }
}
