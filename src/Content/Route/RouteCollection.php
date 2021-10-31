<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void             add(RouteEntity $entity)
 * @method void             set(string $key, RouteEntity $entity)
 * @method RouteEntity[]    getIterator()
 * @method RouteEntity[]    getElements()
 * @method RouteEntity|null get(string $key)
 * @method RouteEntity|null first()
 * @method RouteEntity|null last()
 * @deprecated DAL usage is discouraged. Use route specific actions instead
 */
class RouteCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return RouteEntity::class;
    }
}
