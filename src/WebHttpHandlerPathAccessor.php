<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Shopware\Core\Defaults;

class WebHttpHandlerPathAccessor
{
    private array $known = [];

    private Connection $connection;

    private WebHttpHandlerPathIdResolver $pathIdResolver;

    public function __construct(Connection $connection, WebHttpHandlerPathIdResolver $pathIdResolver)
    {
        $this->connection = $connection;
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
            $nonMatchingBytes = \array_map('hex2bin', $nonMatchingHexes);

            $builder = $this->connection->createQueryBuilder();
            $builder
                ->from('heptaconnect_web_http_handler_path', 'handler_path')
                ->select(['handler_path.id id'])
                ->andWhere($builder->expr()->in('handler_path.id', ':ids'))
                ->setParameter('ids', \array_values($nonMatchingBytes), Connection::PARAM_STR_ARRAY);

            $statement = $builder->execute();

            if (!$statement instanceof Statement) {
                throw new \LogicException('$builder->execute() should have returned a Statement', 1637467898);
            }

            /** @var array<int, string> $rows */
            $rows = $statement->fetchAll(FetchMode::COLUMN);
            $typeIds = \array_map('bin2hex', $rows);
            $foundIds = [];
            $inserts = [];
            $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            foreach ($nonMatchingKeys as $nonMatchingKey) {
                $inserts[$nonMatchingHexes[$nonMatchingKey]] = [
                    'id' => $nonMatchingBytes[$nonMatchingKey],
                    'path' => $nonMatchingKey,
                    'created_at' => $now,
                ];
                $foundIds[$nonMatchingKey] = $nonMatchingHexes[$nonMatchingKey];
            }

            foreach ($typeIds as $typeId) {
                $path = $flippedNonMatchingHexes[$typeId];
                $foundIds[$path] = $typeId;

                unset($inserts[$typeId]);
            }

            foreach ($inserts as $insert) {
                $this->connection->insert('heptaconnect_web_http_handler_path', $insert, [
                    'id' => Type::BINARY,
                ]);
            }

            $this->known = \array_merge($this->known, $foundIds);
        }

        return \array_intersect_key($this->known, \array_fill_keys($httpHandlerPaths, true));
    }
}
