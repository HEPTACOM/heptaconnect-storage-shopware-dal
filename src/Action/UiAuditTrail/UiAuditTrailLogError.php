<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\UiAuditTrail;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogError\UiAuditTrailLogErrorPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\UiAuditTrail\UiAuditTrailLogError\UiAuditTrailLogErrorPayloadCollection;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\UiAuditTrail\UiAuditTrailLogErrorActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\CreateException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\UiAuditTrailStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class UiAuditTrailLogError implements UiAuditTrailLogErrorActionInterface
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function logError(UiAuditTrailLogErrorPayloadCollection $payloads): void
    {
        $inserts = [];

        /** @var UiAuditTrailLogErrorPayload $payload */
        foreach ($payloads as $payload) {
            $key = $payload->getUiAuditTrailKey();

            if (!$key instanceof UiAuditTrailStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($key));
            }

            $inserts[] = [
                'id' => Id::randomBinary(),
                'ui_audit_trail_id' => Id::toBinary($key->getUuid()),
                'logged_at' => DateTime::toStorage($payload->getAt()),
                'depth' => $payload->getDepth(),
                'exception_class' => $payload->getExceptionClass(),
                'code' => $payload->getCode(),
                'message' => $payload->getMessage(),
                'created_at' => DateTime::nowToStorage(),
            ];
        }

        try {
            $this->connection->transactional(static function (Connection $connection) use ($inserts): void {
                // TODO batch
                foreach ($inserts as $insert) {
                    $connection->insert('heptaconnect_ui_audit_trail_error', $insert, [
                        'id' => Types::BINARY,
                        'ui_audit_trail_id' => Types::BINARY,
                    ]);
                }
            });
        } catch (\Throwable $throwable) {
            throw new CreateException(1663694619, $throwable);
        }
    }
}
