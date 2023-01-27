<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class EntityTypeAccessor
{
    public const ENTITY_TYPE_ID_NS = '0d114f3b-c3a9-43da-bc27-3d3ec524a145';

    public const LOOKUP_QUERY = '992a88ac-a232-4d99-b1cc-4165da81ba77';

    /**
     * @var array<string, string>
     */
    private array $entityTypeIds = [];

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory
    ) {
    }

    /**
     * @param array<array-key, class-string<DatasetEntityContract>> $entityTypes
     *
     * @return array<class-string<DatasetEntityContract>, string>
     */
    public function getIdsForTypes(array $entityTypes): array
    {
        $entityTypes = \array_unique($entityTypes);
        $knownKeys = \array_keys($this->entityTypeIds);
        $nonMatchingKeys = \array_diff($entityTypes, $knownKeys);
        $now = DateTime::nowToStorage();

        if ($nonMatchingKeys !== []) {
            $typeIds = $this->queryIdsForTypes($nonMatchingKeys);
            $inserts = [];

            foreach ($nonMatchingKeys as $nonMatchingKey) {
                if (\array_key_exists($nonMatchingKey, $typeIds)) {
                    $this->entityTypeIds[$nonMatchingKey] = $typeIds[$nonMatchingKey];

                    continue;
                }

                $id = Id::hashedBinary(self::ENTITY_TYPE_ID_NS, $nonMatchingKey);
                $inserts[] = [
                    'id' => $id,
                    'type' => $nonMatchingKey,
                    'created_at' => $now,
                ];
                $this->entityTypeIds[$nonMatchingKey] = Id::toHex($id);
            }

            if ($inserts !== []) {
                try {
                    $this->connection->transactional(function () use ($inserts): void {
                        // TODO batch
                        foreach ($inserts as $insert) {
                            $this->connection->insert('heptaconnect_entity_type', $insert, [
                                'id' => Types::BINARY,
                            ]);
                        }
                    });
                } catch (\Throwable $throwable) {
                    throw new CreateException(1642940744, $throwable);
                }
            }
        }

        return \array_intersect_key($this->entityTypeIds, \array_fill_keys($entityTypes, true));
    }

    private function queryIdsForTypes(array $types): array
    {
        $queryBuilder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        $queryBuilder->from('heptaconnect_entity_type', 'type')
            ->select([
                'type.id type_id',
                'type.type type_type',
            ])
            ->andWhere($queryBuilder->expr()->in('type.type', ':types'))
            ->addOrderBy('type.id')
            ->setParameter('types', $types, Connection::PARAM_STR_ARRAY);

        $result = [];

        /** @var array{type_id: string, type_type: string} $row */
        foreach ($queryBuilder->iterateRows() as $row) {
            $result[$row['type_type']] = Id::toHex($row['type_id']);
        }

        return $result;
    }
}
