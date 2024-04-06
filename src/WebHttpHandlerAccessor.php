<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class WebHttpHandlerAccessor
{
    public const FETCH_QUERY = '900bdcb4-3a2a-4092-9eed-f5902e97b02f';

    public function __construct(
        private Connection $connection,
        private QueryFactory $queryFactory,
        private WebHttpHandlerPathIdResolver $pathIdResolver
    ) {
    }

    /**
     * @psalm-param array<array-key, array> $httpHandlerPaths
     *
     * @psalm-return array<array-key, string>
     */
    public function getIdsForHandlers(array $httpHandlerPaths): array
    {
        if ($httpHandlerPaths === []) {
            return [];
        }

        $builder = $this->queryFactory->createBuilder(self::FETCH_QUERY);
        $builder
            ->from('heptaconnect_web_http_handler', 'handler')
            ->select([
                'handler.id id',
                'CONCAT(LOWER(HEX(handler.portal_node_id)), LOWER(HEX(handler.path_id))) `match_key`',
            ])
            ->addOrderBy('handler.id')
            ->addOrderBy('match_key');

        $inserts = [];
        $result = [];
        $now = DateTime::nowToStorage();

        foreach (\array_chunk($httpHandlerPaths, 25, true) as $httpHandlerPathChunks) {
            $b = clone $builder;
            $keyIndex = [];

            foreach ($httpHandlerPathChunks as $key => [$portalNodeKey, $path]) {
                $pathId = $this->pathIdResolver->getIdFromPath($path);
                $match = $portalNodeKey->getUuid() . $pathId;
                $keyIndex[$match] = $key;

                $b->orWhere($b->expr()->and(
                    $b->expr()->eq('handler.portal_node_id', ':pn' . $match),
                    $b->expr()->eq('handler.path_id', ':p' . $match)
                ));
                $b->setParameter('pn' . $match, Id::toBinary($portalNodeKey->getUuid()), Types::BINARY);
                $b->setParameter('p' . $match, Id::toBinary($pathId), Types::BINARY);

                $insertableId = Id::randomBinary();
                $result[$keyIndex[$match]] = Id::toHex($insertableId);
                $inserts[$match] = [
                    'id' => $insertableId,
                    'portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
                    'path_id' => Id::toBinary($pathId),
                    'created_at' => $now,
                ];
            }

            /** @var array{id: string, match_key: string} $row */
            foreach ($b->iterateRows() as $row) {
                $result[$keyIndex[$row['match_key']]] = Id::toHex($row['id']);

                unset($inserts[$row['match_key']]);
            }
        }

        if ($inserts !== []) {
            $this->connection->transactional(function () use ($inserts): void {
                foreach ($inserts as $insert) {
                    $this->connection->insert('heptaconnect_web_http_handler', $insert, [
                        'id' => Types::BINARY,
                        'portal_node_id' => Types::BINARY,
                        'path_id' => Types::BINARY,
                    ]);
                }
            });
        }

        return $result;
    }
}
