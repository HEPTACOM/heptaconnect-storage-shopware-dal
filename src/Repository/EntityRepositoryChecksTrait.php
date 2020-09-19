<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Repository;

use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\NestedEventCollection;

trait EntityRepositoryChecksTrait
{
    private function throwNotFoundWhenNoMatch(
        EntityRepositoryInterface $repository,
        array $primaryKey,
        Context $context
    ): void {
        if ($repository->searchIds(new Criteria([$primaryKey]), $context)->getTotal() < 1) {
            throw new NotFoundException();
        }
    }

    private function throwNotFoundWhenNoChange(EntityWrittenContainerEvent $writtenEvent): void
    {
        $updateResult = $writtenEvent->getEvents();

        if (!$updateResult instanceof NestedEventCollection || $updateResult->count() < 1) {
            throw new NotFoundException();
        }
    }
}
