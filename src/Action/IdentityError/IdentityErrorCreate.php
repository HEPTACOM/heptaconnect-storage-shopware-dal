<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityError;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\IdentityErrorKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityError\Create\IdentityErrorCreateResults;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityError\IdentityErrorCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityErrorStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;

class IdentityErrorCreate implements IdentityErrorCreateActionInterface
{
    public const LOOKUP_QUERY = '95f2537a-eda2-4123-824d-72f6c871e8a8';

    private Connection $connection;

    private QueryFactory $queryFactory;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityTypeAccessor $entityTypeAccessor;

    public function __construct(
        Connection $connection,
        QueryFactory $queryFactory,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityTypeAccessor $entityTypeAccessor
    ) {
        $this->connection = $connection;
        $this->queryFactory = $queryFactory;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityTypeAccessor = $entityTypeAccessor;
    }

    public function create(IdentityErrorCreatePayloads $payloads): IdentityErrorCreateResults
    {
        $lookups = [];

        /** @var IdentityErrorCreatePayload $payload */
        foreach ($payloads as $payload) {
            $portalNodeKey = $payload->getMappingComponent()->getPortalNodeKey();
            $entityType = $payload->getMappingComponent()->getEntityType();
            $externalId = $payload->getMappingComponent()->getExternalId();

            if (!$portalNodeKey instanceof PortalNodeStorageKey) {
                throw new InvalidCreatePayloadException($payload, 1645308762, new UnsupportedStorageKeyException(\get_class($portalNodeKey)));
            }

            $lookups[$portalNodeKey->getUuid()][$entityType][] = $externalId;
        }

        $lookedUps = $this->lookupMappingNodeIds($lookups);
        $insertPayloads = [];

        foreach ($payloads as $payload) {
            /** @var PortalNodeStorageKey $portalNodeKey */
            $portalNodeKey = $payload->getMappingComponent()->getPortalNodeKey();
            $entityType = $payload->getMappingComponent()->getEntityType();
            $externalId = $payload->getMappingComponent()->getExternalId();
            $mappingNodeId = $lookedUps[$portalNodeKey->getUuid()][$entityType][$externalId] ?? null;

            if (!\is_string($mappingNodeId)) {
                throw new InvalidCreatePayloadException($payload, 1645308763);
            }

            $insertPayloads[] = [
                'throwable' => $payload->getThrowable(),
                'portal_node_id' => Id::toBinary($portalNodeKey->getUuid()),
                'mapping_node_id' => Id::toBinary($mappingNodeId),
            ];
        }

        $result = new IdentityErrorCreateResults();
        $now = DateTime::nowToStorage();
        $inserts = [];

        foreach ($insertPayloads as $insertPayload) {
            $resultKey = null;
            $previousKey = null;
            $throwable = $insertPayload['throwable'];
            unset($insertPayload['throwable']);

            $throwables = self::unwrapException($throwable);
            $keys = \iterable_to_array($this->storageKeyGenerator->generateKeys(
                IdentityErrorKeyInterface::class,
                \count($throwables)
            ));

            foreach ($throwables as $exception) {
                $key = \array_shift($keys) ?: null;

                if (!$key instanceof IdentityErrorStorageKey) {
                    throw new UnsupportedStorageKeyException($key === null ? 'null' : \get_class($key));
                }

                $resultKey ??= $key;

                if (!$previousKey instanceof IdentityErrorStorageKey) {
                    $result->push([new IdentityErrorCreateResult($resultKey)]);
                }

                $exceptionAsJson = \json_encode($exception->getTrace(), \JSON_PARTIAL_OUTPUT_ON_ERROR);
                $stackTrace = \is_string($exceptionAsJson) ? $exceptionAsJson : (string) \json_encode([
                    'json_last_error_msg' => \json_last_error_msg(),
                ]);

                $insert = $insertPayload;
                $insert['id'] = Id::toBinary($key->getUuid());
                $insert['previous_id'] = $previousKey instanceof IdentityErrorStorageKey ? Id::toBinary($previousKey->getUuid()) : null;
                $insert['group_previous_id'] = $key->equals($resultKey) ? null : Id::toBinary($resultKey->getUuid());
                $insert['type'] = \get_class($exception);
                $insert['message'] = $exception->getMessage();
                $insert['stack_trace'] = $stackTrace;
                $insert['created_at'] = $now;

                $inserts[] = $insert;
                $previousKey = $key;
            }
        }

        try {
            $this->connection->transactional(function () use ($inserts): void {
                // TODO batch
                foreach ($inserts as $insert) {
                    $this->connection->insert('heptaconnect_mapping_error_message', $insert, [
                        'id' => Types::BINARY,
                        'previous_id' => Types::BINARY,
                        'group_previous_id' => Types::BINARY,
                        'portal_node_id' => Types::BINARY,
                        'mapping_node_id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1645308764, $throwable);
        }

        return $result;
    }

    /**
     * @psalm-return array<array-key, \Throwable>
     */
    private static function unwrapException(\Throwable $exception): array
    {
        $exceptions = [$exception];

        while (($exception = $exception->getPrevious()) instanceof \Throwable) {
            $exceptions[] = $exception;
        }

        return $exceptions;
    }

    private function lookupMappingNodeIds(array $lookups): array
    {
        $builder = $this->getBuilder();
        $builder->andWhere($builder->expr()->eq('portal_node.id', ':portalNodeId'));
        $builder->andWhere($builder->expr()->eq('entity_type.id', ':entityTypeId'));
        $builder->andWhere($builder->expr()->in('mapping.external_id', ':externalIds'));

        $result = [];
        /** @var string[] $allUsedEntityTypes */
        $allUsedEntityTypes = \array_merge([], ...\array_values(\array_map('array_keys', $lookups)));
        $entityTypeIds = Id::toBinaryList($this->entityTypeAccessor->getIdsForTypes($allUsedEntityTypes));

        foreach ($lookups as $portalNodeId => $externalIdsByEntityType) {
            $builder->setParameter('portalNodeId', Id::toBinary($portalNodeId), Types::BINARY);

            foreach ($externalIdsByEntityType as $entityType => $externalIds) {
                $builder->setParameter('entityTypeId', $entityTypeIds[$entityType]);
                $builder->setParameter('externalIds', $externalIds, Connection::PARAM_STR_ARRAY);

                /** @var array{portal_node_id: string, entity_type_type: string, mapping_external_id: string, mapping_node_id: string} $match */
                foreach ($builder->iterateRows() as $match) {
                    $matchPortalNodeId = Id::toHex($match['portal_node_id']);
                    $matchMappingNodeId = Id::toHex($match['mapping_node_id']);
                    $matchExternalId = $match['mapping_external_id'];
                    $matchEntityType = $match['entity_type_type'];

                    $result[$matchPortalNodeId][$matchEntityType][$matchExternalId] = $matchMappingNodeId;
                }
            }
        }

        return $result;
    }

    private function getBuilder(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::LOOKUP_QUERY);

        $builder->from('heptaconnect_mapping', 'mapping')
            ->innerJoin(
                'mapping',
                'heptaconnect_portal_node',
                'portal_node',
                $builder->expr()->eq('mapping.portal_node_id', 'portal_node.id')
            )
            ->innerJoin(
                'mapping',
                'heptaconnect_mapping_node',
                'mapping_node',
                $builder->expr()->eq('mapping.mapping_node_id', 'mapping_node.id')
            )
            ->innerJoin(
                'mapping_node',
                'heptaconnect_entity_type',
                'entity_type',
                $builder->expr()->eq('mapping_node.type_id', 'entity_type.id')
            )
            ->addOrderBy('mapping.id')
            ->select([
                'portal_node.id portal_node_id',
                'entity_type.type entity_type_type',
                'mapping.external_id mapping_external_id',
                'mapping_node.id mapping_node_id',
            ])
            ->andWhere($builder->expr()->isNull('portal_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping_node.deleted_at'))
            ->andWhere($builder->expr()->isNull('mapping.deleted_at'));

        return $builder;
    }
}
