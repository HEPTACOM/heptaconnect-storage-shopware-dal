<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class RouteCapabilityAccessor
{
    public const FETCH_QUERY = '93fd2b30-ca58-4d60-b29e-d14115b5ea2b';

    private array $knownCapabilities = [];

    public function __construct(private QueryFactory $queryFactory)
    {
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
            $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);
            $builder
                ->from('heptaconnect_route_capability', 'route_capability')
                ->select([
                    'route_capability.id id',
                    'route_capability.name name',
                ])
                ->addOrderBy('route_capability.id')
                ->andWhere($builder->expr()->in('route_capability.name', ':names'))
                ->setParameter('names', $nonMatchingKeys, Connection::PARAM_STR_ARRAY);

            $typeIds = [];

            /** @var object{id: string, name: string} $row */
            foreach ($builder->iterateRows() as $row) {
                $typeIds[$row['name']] = Id::toHex($row['id']);
            }

            $this->knownCapabilities = \array_merge($this->knownCapabilities, $typeIds);
        }

        return \array_intersect_key($this->knownCapabilities, \array_fill_keys($capabilities, true));
    }
}
