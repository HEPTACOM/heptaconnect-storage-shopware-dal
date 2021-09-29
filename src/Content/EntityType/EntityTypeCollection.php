<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                  add(EntityTypeEntity $entity)
 * @method void                  set(string $key, EntityTypeEntity $entity)
 * @method EntityTypeEntity[]    getIterator()
 * @method EntityTypeEntity[]    getElements()
 * @method EntityTypeEntity|null get(string $key)
 * @method EntityTypeEntity|null first()
 * @method EntityTypeEntity|null last()
 */
class EntityTypeCollection extends EntityCollection
{
    /**
     * @psalm-return array<class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>, string>
     */
    public function groupByType(): array
    {
        $result = [];

        /** @var EntityTypeEntity $datasetEntity */
        foreach ($this as $datasetEntity) {
            $result[$datasetEntity->getType()] = $datasetEntity->getId();
        }

        return $result;
    }

    protected function getExpectedClass(): string
    {
        return EntityTypeEntity::class;
    }
}
