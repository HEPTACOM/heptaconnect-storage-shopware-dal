<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\UiAuditTrail;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailEnd\UiAuditTrailEndPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailEndActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\UiAuditTrailStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class UiAuditTrailEnd implements UiAuditTrailEndActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function end(UiAuditTrailEndPayload $payload): void
    {
        $key = $payload->getUiAuditTrailKey();

        if (!$key instanceof UiAuditTrailStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        try {
            $this->connection->transactional(static fn (Connection $connection) => $connection->update(
                'heptaconnect_ui_audit_trail',
                [
                    'finished_at' => DateTime::toStorage($payload->getAt()),
                ],
                [
                    'id' => Id::toBinary($key->getUuid()),
                ],
                [
                    'id' => Types::BINARY,
                ]
            ));
        } catch (\Throwable $throwable) {
            throw new CreateException(1663694617, $throwable);
        }
    }
}
