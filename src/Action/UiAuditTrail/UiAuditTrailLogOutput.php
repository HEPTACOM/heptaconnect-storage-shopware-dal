<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\UiAuditTrail;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogOutput\UiAuditTrailLogOutputPayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailLogOutputActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\UiAuditTrailStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class UiAuditTrailLogOutput implements UiAuditTrailLogOutputActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function logOutput(UiAuditTrailLogOutputPayload $payload): void
    {
        $key = $payload->getUiAuditTrailKey();

        if (!$key instanceof UiAuditTrailStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $encoded = (string) \json_encode($payload->getOutput(), \JSON_PARTIAL_OUTPUT_ON_ERROR);
        $compressed = \gzcompress($encoded);

        try {
            $this->connection->transactional(static fn (Connection $connection) => $connection->insert(
                'heptaconnect_ui_audit_trail_data',
                [
                    'id' => Id::randomBinary(),
                    'ui_audit_trail_id' => Id::toBinary($key->getUuid()),
                    'payload' => $compressed,
                    'payload_format' => 'json+gzpress',
                    'created_at' => DateTime::nowToStorage(),
                ],
                [
                    'id' => Types::BINARY,
                    'ui_audit_trail_id' => Types::BINARY,
                    'payload' => Types::BINARY,
                ]
            ));
        } catch (\Throwable $throwable) {
            throw new CreateException(1663694618, $throwable);
        }
    }
}
