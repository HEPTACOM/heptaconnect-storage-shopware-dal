<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Types\Types;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class JobTypeAccessor
{
    private array $known = [];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
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
            $builder = $this->connection->createQueryBuilder();
            $builder
                ->from('heptaconnect_job_type', 'job_type')
                ->select([
                    'job_type.id id',
                    'job_type.type type',
                ])
                ->andWhere($builder->expr()->in('job_type.type', ':types'))
                ->setParameter('types', $nonMatchingKeys, Connection::PARAM_STR_ARRAY);

            $statement = $builder->execute();

            if (!$statement instanceof ResultStatement) {
                throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1639265928);
            }

            $rows = $statement->fetchAll(FetchMode::ASSOCIATIVE);
            $typeIds = \array_column($rows, 'id', 'type');
            $typeIds = \array_map('bin2hex', $typeIds);
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
