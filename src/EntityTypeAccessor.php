<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class EntityTypeAccessor
{
    public const ENTITY_TYPE_ID_NS = '0d114f3b-c3a9-43da-bc27-3d3ec524a145';

    private array $entityTypeIds = [];

    private EntityRepositoryInterface $entityTypes;

    public function __construct(EntityRepositoryInterface $entityTypes)
    {
        $this->entityTypes = $entityTypes;
    }

    /**
     * @psalm-param array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>> $entityTypes
     * @psalm-return array<class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>, string>
     */
    public function getIdsForTypes(array $entityTypes, Context $context): array
    {
        $entityTypes = \array_unique($entityTypes);
        $knownKeys = \array_keys($this->entityTypeIds);
        $nonMatchingKeys = \array_diff($entityTypes, $knownKeys);

        if ($nonMatchingKeys !== []) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('type', $nonMatchingKeys));
            /** @var EntityTypeCollection $datasetTypeEntities */
            $datasetTypeEntities = $this->entityTypes->search($criteria, $context)->getEntities();
            $typeIds = $datasetTypeEntities->groupByType();
            $inserts = [];

            foreach ($nonMatchingKeys as $nonMatchingKey) {
                if (\array_key_exists($nonMatchingKey, $typeIds)) {
                    $this->entityTypeIds[$nonMatchingKey] = $typeIds[$nonMatchingKey];

                    continue;
                }

                $id = (string) Uuid::uuid5(self::ENTITY_TYPE_ID_NS, $nonMatchingKey)->getHex();
                $inserts[] = [
                    'id' => $id,
                    'type' => $nonMatchingKey,
                ];
                $this->entityTypeIds[$nonMatchingKey] = $id;
            }

            if ($inserts !== []) {
                $this->entityTypes->create($inserts, $context);
            }
        }

        return \array_intersect_key($this->entityTypeIds, \array_fill_keys($entityTypes, true));
    }
}
