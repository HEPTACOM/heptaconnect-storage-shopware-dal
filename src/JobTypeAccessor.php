<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class JobTypeAccessor
{
    public const string LOOKUP_QUERY = '28ef8980-146b-416c-8338-f1e394ac8c5f';

    /**
     * @var array<string, string>
     */
    private array $known = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly QueryFactory $queryFactory
    ) {
    }

    /**
     * @param array<array-key, string> $types
     *
     * @return array<string, string>
     */
    public function getIdsForTypes(array $types): array
    {
        $types = \array_keys(\array_flip($types));
        $knownKeys = \array_keys($this->known);
        $nonMatchingKeys = \array_diff($types, $knownKeys);

        if ($nonMatchingKeys !== []) {
            $builder = $this->queryFactory->createSelectBuilder(self::LOOKUP_QUERY);
            $builder
                ->from('heptaconnect_job_type', 'job_type')
                ->select([
                    'job_type.id id',
                    'job_type.name type',
                ])
                ->andWhere($builder->expr()->in('job_type.name', ':types'))
                ->setParameter('types', $nonMatchingKeys, ArrayParameterType::STRING);

            $typeIds = [];

            /** @var array{id: string, type: string} $row */
            foreach ($builder->iterateRows('job_type.id') as $row) {
                $typeIds[$row['type']] = Id::toHex($row['id']);
            }

            $inserts = [];
            $now = DateTime::nowToStorage();

            foreach ($types as $type) {
                if (!\array_key_exists($type, $typeIds)) {
                    $id = Id::randomBinary();
                    $inserts[] = [
                        'id' => $id,
                        'name' => $type,
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
