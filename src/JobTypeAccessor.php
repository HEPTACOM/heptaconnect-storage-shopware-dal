<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class JobTypeAccessor
{
    private array $known = [];

    private Connection $connection;

    private int $queryFallbackPageSize;

    public function __construct(Connection $connection, int $queryFallbackPageSize)
    {
        $this->connection = $connection;
        $this->queryFallbackPageSize = $queryFallbackPageSize;
    }

    /**
     * @psalm-param array<array-key, string> $types
     * @psalm-return array<string, string>
     */
    public function getIdsForTypes(array $types): array
    {
        $types = \array_keys(\array_flip($types));
        $knownKeys = \array_keys($this->known);
        $nonMatchingKeys = \array_diff($types, $knownKeys);

        if ($nonMatchingKeys !== []) {
            $builder = new QueryBuilder($this->connection);
            $builder
                ->from('heptaconnect_job_type', 'job_type')
                ->select([
                    'job_type.id id',
                    'job_type.type type',
                ])
                ->addOrderBy('job_type.id')
                ->andWhere($builder->expr()->in('job_type.type', ':types'))
                ->setParameter('types', $nonMatchingKeys, Connection::PARAM_STR_ARRAY);

            $typeIds = [];

            foreach ($builder->fetchAssocPaginated($this->queryFallbackPageSize) as $row) {
                $typeIds[$row['type']] = \bin2hex($row['id']);
            }

            $inserts = [];
            $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            foreach ($types as $type) {
                if (!\array_key_exists($type, $typeIds)) {
                    $id = Uuid::uuid4()->getBytes();
                    $inserts[] = [
                        'id' => $id,
                        'type' => $type,
                        'created_at' => $now,
                    ];
                    $typeIds[$type] = \bin2hex($id);
                }
            }

            foreach ($inserts as $insert) {
                $this->connection->insert('heptaconnect_job_type', $insert, [
                    'id' => Types::BINARY,
                ]);
            }

            $this->known = \array_merge($this->known, $typeIds);
        }

        return \array_intersect_key($this->known, \array_fill_keys($types, true));
    }
}
