<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Webhook;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void               add(WebhookEntity $entity)
 * @method void               set(string $key, WebhookEntity $entity)
 * @method WebhookEntity[]    getIterator()
 * @method WebhookEntity[]    getElements()
 * @method WebhookEntity|null get(string $key)
 * @method WebhookEntity|null first()
 * @method WebhookEntity|null last()
 */
class WebhookCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WebhookEntity::class;
    }
}
