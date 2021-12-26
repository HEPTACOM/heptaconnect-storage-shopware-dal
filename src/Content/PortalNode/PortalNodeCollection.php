<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                  add(PortalNodeEntity $entity)
 * @method void                  set(string $key, PortalNodeEntity $entity)
 * @method PortalNodeEntity[]    getIterator()
 * @method PortalNodeEntity[]    getElements()
 * @method PortalNodeEntity|null get(string $key)
 * @method PortalNodeEntity|null first()
 * @method PortalNodeEntity|null last()
 *
 * @deprecated DAL usage is discouraged. Use portal node specific actions instead
 */
class PortalNodeCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return PortalNodeEntity::class;
    }
}
