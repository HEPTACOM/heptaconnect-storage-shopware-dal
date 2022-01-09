<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @internal
 *
 * @method void               add(CronjobEntity $entity)
 * @method void               set(string $key, CronjobEntity $entity)
 * @method CronjobEntity[]    getIterator()
 * @method CronjobEntity[]    getElements()
 * @method CronjobEntity|null get(string $key)
 * @method CronjobEntity|null first()
 * @method CronjobEntity|null last()
 */
class CronjobCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CronjobEntity::class;
    }
}
