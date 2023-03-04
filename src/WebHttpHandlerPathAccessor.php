<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class WebHttpHandlerPathAccessor
{
    public const FETCH_QUERY = 'f683453e-336f-4913-8bb9-aa0e34745f97';

    /**
     * @var array<string, string>
     */
    private array $known = [];

    private Connection $connection;

    private QueryFactory $queryFactory;

    private WebHttpHandlerPathIdResolver $pathIdResolver;

    public function __construct(
        Connection $connection,
        QueryFactory $queryFactory,
        WebHttpHandlerPathIdResolver $pathIdResolver
    ) {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
        $this->pathIdResolver = $pathIdResolver;
    }

    /**
     * @psalm-param array<array-key, string> $httpHandlerPaths
     * @psalm-return array<string, string>
     */
    public function getIdsForPaths(array $httpHandlerPaths): array
    {
        $httpHandlerPaths = \array_unique($httpHandlerPaths);
        $knownKeys = \array_keys($this->known);
        $nonMatchingKeys = \array_diff($httpHandlerPaths, $knownKeys);

        if ($nonMatchingKeys !== []) {
            $nonMatchingHexes = \array_combine($nonMatchingKeys, \array_map([$this->pathIdResolver, 'getIdFromPath'], $nonMatchingKeys));

            if (!\is_array($nonMatchingHexes)) {
                throw new \LogicException('array_combine should not have returned false', 1637467897);
            }

            $flippedNonMatchingHexes = \array_flip($nonMatchingHexes);
            $nonMatchingBytes = Id::toBinaryList($nonMatchingHexes);

            $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);
            $builder
                ->from('heptaconnect_web_http_handler_path', 'handler_path')
                ->select(['handler_path.id id'])
                ->addOrderBy('handler_path.id')
                ->andWhere($builder->expr()->in('handler_path.id', ':ids'))
                ->setParameter('ids', \array_values($nonMatchingBytes), Connection::PARAM_STR_ARRAY);

            $foundIds = [];
            $inserts = [];
            $now = DateTime::nowToStorage();

            foreach ($nonMatchingKeys as $nonMatchingKey) {
                $inserts[$nonMatchingHexes[$nonMatchingKey]] = [
                    'id' => $nonMatchingBytes[$nonMatchingKey],
                    'path' => $nonMatchingKey,
                    'created_at' => $now,
                ];
                $foundIds[$nonMatchingKey] = $nonMatchingHexes[$nonMatchingKey];
            }

            foreach (Id::toHexIterable($builder->iterateColumn()) as $typeId) {
                $path = $flippedNonMatchingHexes[$typeId];
                $foundIds[$path] = $typeId;

                unset($inserts[$typeId]);
            }

            foreach ($inserts as $insert) {
                $this->connection->insert('heptaconnect_web_http_handler_path', $insert, [
                    'id' => Types::BINARY,
                ]);
            }

            $this->known = \array_merge($this->known, $foundIds);
        }

        return \array_intersect_key($this->known, \array_fill_keys($httpHandlerPaths, true));
    }
}
