<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class JobTypeAccessor
{
    public const LOOKUP_QUERY = '28ef8980-146b-416c-8338-f1e394ac8c5f';

    private array $known = [];

    private Connection $connection;

    private QueryFactory $queryFactory;

    public function __construct(Connection $connection, QueryFactory $queryFactory)
    {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
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
            $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);
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

            foreach ($builder->iterateRows() as $row) {
                $typeIds[$row['type']] = Id::toHex($row['id']);
            }

            $inserts = [];
            $now = DateTime::nowToStorage();

            foreach ($types as $type) {
                if (!\array_key_exists($type, $typeIds)) {
                    $id = Id::randomBinary();
                    $inserts[] = [
                        'id' => $id,
                        'type' => $type,
                        'created_at' => $now,
                    ];
                    $typeIds[$type] = Id::toHex($id);
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
