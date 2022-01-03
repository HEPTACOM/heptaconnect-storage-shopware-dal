<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\FetchMode;

class RouteCapabilityAccessor
{
    private array $knownCapabilities = [];

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @psalm-param array<array-key, string> $capabilities
     * @psalm-return array<string, string>
     */
    public function getIdsForNames(array $capabilities): array
    {
        $capabilities = \array_keys(\array_flip($capabilities));
        $knownKeys = \array_keys($this->knownCapabilities);
        $nonMatchingKeys = \array_diff($capabilities, $knownKeys);

        if ($nonMatchingKeys !== []) {
            $builder = $this->connection->createQueryBuilder();
            $builder
                ->from('heptaconnect_route_capability', 'route_capability')
                ->select([
                    'route_capability.id id',
                    'route_capability.name name',
                ])
                ->andWhere($builder->expr()->in('route_capability.name', ':names'))
                ->setParameter('names', $nonMatchingKeys, Connection::PARAM_STR_ARRAY);

            $statement = $builder->execute();

            if (!$statement instanceof ResultStatement) {
                throw new \LogicException('$builder->execute() should have returned a ResultStatement', 1637467901);
            }

            $rows = $statement->fetchAll(FetchMode::ASSOCIATIVE);
            $typeIds = \array_column($rows, 'id', 'name');
            $typeIds = \array_map('bin2hex', $typeIds);
            $this->knownCapabilities = \array_merge($this->knownCapabilities, $typeIds);
        }

        return \array_intersect_key($this->knownCapabilities, \array_fill_keys($capabilities, true));
    }
}
