<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Shopware\Core\Defaults;

class EntityTypeAccessor
{
    public const ENTITY_TYPE_ID_NS = '0d114f3b-c3a9-43da-bc27-3d3ec524a145';

    public const LOOKUP_QUERY = '992a88ac-a232-4d99-b1cc-4165da81ba77';

    private array $entityTypeIds = [];

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
    }

    /**
     * @psalm-param array<array-key, class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>> $entityTypes
     * @psalm-return array<class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>, string>
     */
    public function getIdsForTypes(array $entityTypes): array
    {
        $entityTypes = \array_unique($entityTypes);
        $knownKeys = \array_keys($this->entityTypeIds);
        $nonMatchingKeys = \array_diff($entityTypes, $knownKeys);
        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

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

        foreach ($queryBuilder->iterateRows() as $row) {
            $result[$row['type_type']] = Id::toHex($row['type_id']);
        }

        return $result;
    }
}
