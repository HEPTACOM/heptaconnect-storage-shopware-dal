<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\PortalNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Event\NestedEventCollection;

class PortalNodeRepository extends PortalNodeRepositoryContract
{
    private EntityRepositoryInterface $portalNodes;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        EntityRepositoryInterface $portalNodes,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->portalNodes = $portalNodes;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function read(PortalNodeKeyInterface $portalNodeKey): string
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $criteria = (new Criteria([$portalNodeKey->getUuid()]))
            ->addFilter(new EqualsFilter('deletedAt', null));

        $portalNode = $this->portalNodes->search($criteria, Context::createDefaultContext())->first();

        if (!$portalNode instanceof PortalNodeEntity) {
            throw new NotFoundException();
        }

        return $portalNode->getClassName();
    }

    public function listAll(): iterable
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('deletedAt', null));

        $iterator = new RepositoryIterator($this->portalNodes, Context::createDefaultContext(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new PortalNodeStorageKey($id);
            }
        }
    }

    public function listByClass(string $className): iterable
    {
        $criteria = (new Criteria())->addFilter(
            new EqualsFilter('deletedAt', null),
            new EqualsFilter('className', $className)
        );

        $iterator = new RepositoryIterator($this->portalNodes, Context::createDefaultContext(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new PortalNodeStorageKey($id);
            }
        }
    }

    public function create(string $className): PortalNodeKeyInterface
    {
        $portalNodeKey = $this->storageKeyGenerator->generateKey(PortalNodeKeyInterface::class);

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $this->portalNodes->create([[
            'id' => $portalNodeKey->getUuid(),
            'className' => $className,
        ]], Context::createDefaultContext());

        return $portalNodeKey;
    }

    public function delete(PortalNodeKeyInterface $portalNodeKey): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        try {
            $updateResult = $this->portalNodes->update([[
                'id' => $portalNodeKey->getUuid(),
                'deletedAt' => \date_create(),
            ]], Context::createDefaultContext())->getEvents();
        } catch (\Throwable $throwable) {
            // TODO log
            return;
        }

        if (!$updateResult instanceof NestedEventCollection || $updateResult->count() < 1) {
            throw new NotFoundException();
        }
    }
}
