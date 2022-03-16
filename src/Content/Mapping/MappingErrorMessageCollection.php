<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                           add(MappingErrorMessageEntity $entity)
 * @method void                           set(string $key, MappingErrorMessageEntity $entity)
 * @method MappingErrorMessageEntity[]    getIterator()
 * @method MappingErrorMessageEntity[]    getElements()
 * @method MappingErrorMessageEntity|null get(string $key)
 * @method MappingErrorMessageEntity|null first()
 * @method MappingErrorMessageEntity|null last()
 */
class MappingErrorMessageCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MappingErrorMessageEntity::class;
    }
}
