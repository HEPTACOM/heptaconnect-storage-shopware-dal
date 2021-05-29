<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(JobTypeEntity $entity)
 * @method void               set(string $key, JobTypeEntity $entity)
 * @method JobTypeEntity[]    getIterator()
 * @method JobTypeEntity[]    getElements()
 * @method JobTypeEntity|null get(string $key)
 * @method JobTypeEntity|null first()
 * @method JobTypeEntity|null last()
 */
class JobTypeCollection extends EntityCollection
{
    /**
     * @psalm-return array<string, string>
     */
    public function groupByType(): array
    {
        $result = [];

        /** @var JobTypeEntity $type */
        foreach ($this as $type) {
            $result[$type->getType()] = $type->getId();
        }

        return $result;
    }

    protected function getExpectedClass(): string
    {
        return JobTypeEntity::class;
    }
}
