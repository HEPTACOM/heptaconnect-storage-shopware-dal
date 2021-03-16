<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\Parallelization\Exception\ResourceIsLockedException;
use Heptacom\HeptaConnect\Storage\Base\Contract\ResourceLockStorageContract;
use Symfony\Component\Lock\LockFactory;

class ResourceLockStorage extends ResourceLockStorageContract
{
    private LockFactory $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    public function create(string $key): void
    {
        try {
            $this->lockFactory->createLock($key)->acquire();
        } catch (\Throwable $throwable) {
            throw new ResourceIsLockedException($key, null);
        }
    }

    public function has(string $key): bool
    {
        return $this->lockFactory->createLock($key)->isAcquired();
    }

    public function delete(string $key): void
    {
        $this->lockFactory->createLock($key)->release();
    }
}
