<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class EntityTypeAccessor
{
    public const ENTITY_TYPE_ID_NS = '0d114f3b-c3a9-43da-bc27-3d3ec524a145';

    private array $entityTypeIds = [];

    private Connection $connection;

    private int $queryFallbackPageSize;

    public function __construct(Connection $connection, int $queryFallbackPageSize)
    {
        $this->connection = $connection;
        $this->queryFallbackPageSize = $queryFallbackPageSize;
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

                $id = Uuid::uuid5(self::ENTITY_TYPE_ID_NS, $nonMatchingKey)->getBytes();
                $inserts[] = [
                    'id' => $id,
                    'type' => $nonMatchingKey,
                    'created_at' => $now,
                ];
                $this->entityTypeIds[$nonMatchingKey] = \bin2hex($id);
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
        $queryBuilder = new QueryBuilder($this->connection);

        $queryBuilder->from('heptaconnect_entity_type', 'type')
            ->select([
                'type.id type_id',
                'type.type type_type',
            ])
            ->andWhere($queryBuilder->expr()->in('type.type', ':types'))
            ->addOrderBy('type.id')
            ->setParameter('types', $types, Connection::PARAM_STR_ARRAY);

        $result = [];

        foreach ($queryBuilder->fetchAssocPaginated($this->queryFallbackPageSize) as $row) {
            $result[$row['type_type']] = \bin2hex($row['type_id']);
        }

        return $result;
    }
}
