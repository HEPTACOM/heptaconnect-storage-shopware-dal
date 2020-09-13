<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                   add(MappingNodeEntity $entity)
 * @method void                   set(string $key, MappingNodeEntity $entity)
 * @method MappingNodeEntity[]    getIterator()
 * @method MappingNodeEntity[]    getElements()
 * @method MappingNodeEntity|null get(string $key)
 * @method MappingNodeEntity|null first()
 * @method MappingNodeEntity|null last()
 */
class MappingNodeCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return MappingNodeEntity::class;
    }
}
