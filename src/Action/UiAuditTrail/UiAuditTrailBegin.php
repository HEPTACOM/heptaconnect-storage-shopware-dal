<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\UiAuditTrail;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailBegin\UiAuditTrailBeginPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailBegin\UiAuditTrailBeginResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailBeginActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\UiAuditTrailKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\UiAuditTrailStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class UiAuditTrailBegin implements UiAuditTrailBeginActionInterface
{
    private StorageKeyGeneratorContract $storageKeyGenerator;

    private Connection $connection;

    public function __construct(
        StorageKeyGeneratorContract $storageKeyGenerator,
        Connection $connection
    ) {
        $this->storageKeyGenerator = $storageKeyGenerator;
        $this->connection = $connection;
    }

    public function begin(UiAuditTrailBeginPayload $payload): UiAuditTrailBeginResult
    {
        $key = \iterable_to_array($this->storageKeyGenerator->generateKeys(UiAuditTrailKeyInterface::class, 1))[0] ?? null;

        if (!$key instanceof UiAuditTrailStorageKey) {
            throw new UnsupportedStorageKeyException($key === null ? StorageKeyInterface::class : \get_class($key));
        }

        try {
            $this->connection->transactional(static fn (Connection $connection) => $connection->insert(
                'heptaconnect_ui_audit_trail',
                [
                    'id' => Id::toBinary($key->getUuid()),
                    'ui_type' => $payload->getUiType(),
                    'ui_action_type' => (string) $payload->getUiActionType(),
                    'ui_identifier' => $payload->getUiIdentifier(),
                    'user_identifier' => $payload->getUserIdentifier(),
                    'started_at' => DateTime::toStorage($payload->getAt()),
                    'created_at' => DateTime::nowToStorage(),
                    'finished_at' => null,
                ],
                [
                    'id' => Types::BINARY,
                ]
            ));
        } catch (\Throwable $throwable) {
            throw new CreateException(1663694616, $throwable);
        }

        return new UiAuditTrailBeginResult($key);
    }
}
