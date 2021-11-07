<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Contract\RouteCreateResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\EntityTypeAccessor;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class RouteCreate implements RouteCreateActionInterface
{
    private Connection $connection;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    private EntityTypeAccessor $entityTypes;

    public function __construct(
        Connection $connection,
        StorageKeyGeneratorContract $storageKeyGenerator,
        EntityTypeAccessor $entityTypes
    ) {
        $this->connection = $connection;
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->entityTypes = $entityTypes;
    }

    public function create(RouteCreatePayloads $params): iterable
    {
        $payload = [];

        /** @var RouteCreatePayload $param */
        foreach ($params as $param) {
            $sourceKey = $param->getSource();

            if (!$sourceKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($sourceKey));
            }

            $targetKey = $param->getTarget();

            if (!$targetKey instanceof PortalNodeStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($targetKey));
            }

            $payload[] = [
                'type' => $param->getEntityType(),
                'sourceKey' => $sourceKey->getUuid(),
                'targetKey' => $targetKey->getUuid(),
            ];
        }

        $entityTypeIds = $this->entityTypes->getIdsForTypes(\array_column($payload, 'type'), Context::createDefaultContext());
        $keys = $this->storageKeyGenerator->generateKeys(RouteKeyInterface::class, \count($payload));

        foreach ($keys as $key) {
            if (!$key instanceof RouteStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $data = \array_shift($payload);

            if (!\is_array($data)) {
                continue;
            }

            $this->connection->insert('heptaconnect_route', [
                'id' => Uuid::fromHexToBytes($key->getUuid()),
                'source_id' => Uuid::fromHexToBytes((string) $data['sourceKey']),
                'target_id' => Uuid::fromHexToBytes((string) $data['targetKey']),
                'type_id' => Uuid::fromHexToBytes($entityTypeIds[(string) $data['type']]),
                'created_at' => \date_create()->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ], [
                'id' => Types::BINARY,
                'source_id' => Types::BINARY,
                'target_id' => Types::BINARY,
                'type_id' => Types::BINARY,
            ]);

            yield new RouteCreateResult($key);
        }
    }
}
