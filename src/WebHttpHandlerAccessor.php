<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class WebHttpHandlerAccessor
{
    private Connection $connection;

    private WebHttpHandlerPathIdResolver $pathIdResolver;

    public function __construct(Connection $connection, WebHttpHandlerPathIdResolver $pathIdResolver)
    {
        $this->connection = $connection;
        $this->pathIdResolver = $pathIdResolver;
    }

    /**
     * @psalm-param array<array-key, array> $httpHandlerPaths
     * @psalm-return array<array-key, string>
     */
    public function getIdsForHandlers(array $httpHandlerPaths): array
    {
        if ($httpHandlerPaths === []) {
            return [];
        }

        $builder = $this->connection->createQueryBuilder();
        $builder
            ->from('heptaconnect_web_http_handler', 'handler')
            ->select([
                'handler.id id',
                'CONCAT(LOWER(HEX(handler.portal_node_id)), LOWER(HEX(handler.path_id))) `match_key`',
            ]);

        $inserts = [];
        $result = [];
        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach (\array_chunk($httpHandlerPaths, 25, true) as $httpHandlerPathChunks) {
            $b = clone $builder;
            $keyIndex = [];

            foreach ($httpHandlerPathChunks as $key => [$portalNodeKey, $path]) {
                $pathId = $this->pathIdResolver->getIdFromPath($path);
                $match = $portalNodeKey->getUuid() . $pathId;
                $keyIndex[$match] = $key;

                $b->orWhere($b->expr()->andX(
                    $b->expr()->eq('handler.portal_node_id', ':pn' . $match),
                    $b->expr()->eq('handler.path_id', ':p' . $match)
                ));
                $b->setParameter('pn' . $match, \hex2bin($portalNodeKey->getUuid()), Type::BINARY);
                $b->setParameter('p' . $match, \hex2bin($pathId), Type::BINARY);

                $insertableId = Uuid::uuid4()->getBytes();
                $result[$keyIndex[$match]] = \bin2hex($insertableId);
                $inserts[$match] = [
                    'id' => $insertableId,
                    'portal_node_id' => \hex2bin($portalNodeKey->getUuid()),
                    'path_id' => \hex2bin($pathId),
                    'created_at' => $now,
                ];
            }

            $statement = $b->execute();

            if (!$statement instanceof Statement) {
                throw new \LogicException('$b->execute() should have returned a Statement', 1637467899);
            }

            /** @var array<int, string[]> $rows */
            $rows = $statement->fetchAll(FetchMode::ASSOCIATIVE);
            $keyedRows = \array_column($rows, 'id', 'match_key');

            foreach ($keyedRows as $match => $foundId) {
                $result[$keyIndex[$match]] = \bin2hex($foundId);

                unset($inserts[$match]);
            }
        }

        if ($inserts !== []) {
            $this->connection->transactional(function () use ($inserts): void {
                foreach ($inserts as $insert) {
                    $this->connection->insert('heptaconnect_web_http_handler', $insert, [
                        'id' => Type::BINARY,
                        'portal_node_id' => Type::BINARY,
                        'path_id' => Type::BINARY,
                    ]);
                }
            });
        }

        return $result;
    }
}
