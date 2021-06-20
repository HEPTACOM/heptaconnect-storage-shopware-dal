<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Repository\PortalNodeRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class PortalNodeRepository extends PortalNodeRepositoryContract
{
    use EntityRepositoryChecksTrait;

    private EntityRepositoryInterface $portalNodes;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private ContextFactory $contextFactory;

    public function __construct(
        EntityRepositoryInterface $portalNodes,
        StorageKeyGeneratorContract $storageKeyGenerator,
        ContextFactory $contextFactory
    ) {
        $this->portalNodes = $portalNodes;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->contextFactory = $contextFactory;
    }

    public function read(PortalNodeKeyInterface $portalNodeKey): string
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return $portalNodeKey->getPortalType();
        }

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $criteria = (new Criteria([$portalNodeKey->getUuid()]))
            ->addFilter(new EqualsFilter('deletedAt', null));

        $portalNode = $this->portalNodes->search($criteria, $this->contextFactory->create())->first();

        if (!$portalNode instanceof PortalNodeEntity) {
            throw new NotFoundException();
        }

        /** @var class-string<\Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract> $className */
        $className = $portalNode->getClassName();

        return $className;
    }

    public function listAll(): iterable
    {
        $criteria = (new Criteria())->setLimit(50)->addFilter(new EqualsFilter('deletedAt', null));

        $iterator = new RepositoryIterator($this->portalNodes, $this->contextFactory->create(), $criteria);

        while (!\is_null($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new PortalNodeStorageKey($id);
            }
        }
    }

    public function listByClass(string $className): iterable
    {
        $criteria = (new Criteria())->setLimit(50)->addFilter(
            new EqualsFilter('deletedAt', null),
            new EqualsFilter('className', $className)
        );

        $iterator = new RepositoryIterator($this->portalNodes, $this->contextFactory->create(), $criteria);

        while (!\is_null($ids = $iterator->fetchIds())) {
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
        ]], $this->contextFactory->create());

        return $portalNodeKey;
    }

    public function delete(PortalNodeKeyInterface $portalNodeKey): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $context = $this->contextFactory->create();
        $this->throwNotFoundWhenNoMatch($this->portalNodes, $portalNodeKey->getUuid(), $context);
        $this->throwNotFoundWhenNoChange($this->portalNodes->update([[
            'id' => $portalNodeKey->getUuid(),
            'deletedAt' => \date_create(),
        ]], $context));
    }
}
