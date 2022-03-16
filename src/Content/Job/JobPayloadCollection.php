<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                  add(JobPayloadEntity $entity)
 * @method void                  set(string $key, JobPayloadEntity $entity)
 * @method JobPayloadEntity[]    getIterator()
 * @method JobPayloadEntity[]    getElements()
 * @method JobPayloadEntity|null get(string $key)
 * @method JobPayloadEntity|null first()
 * @method JobPayloadEntity|null last()
 *
 * @deprecated DAL usage is discouraged. Use job specific actions instead
 */
class JobPayloadCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return JobPayloadEntity::class;
    }
}
