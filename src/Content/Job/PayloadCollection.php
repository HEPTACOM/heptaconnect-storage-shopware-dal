<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(PayloadEntity $entity)
 * @method void               set(string $key, PayloadEntity $entity)
 * @method PayloadEntity[]    getIterator()
 * @method PayloadEntity[]    getElements()
 * @method PayloadEntity|null get(string $key)
 * @method PayloadEntity|null first()
 * @method PayloadEntity|null last()
 */
class PayloadCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PayloadEntity::class;
    }
}
