<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\FileReference;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestPersist\FileReferencePersistRequestPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestPersist\FileReferencePersistRequestResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferencePersistRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\InvalidCreatePayloadException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;

final class FileReferencePersistRequestAction implements FileReferencePersistRequestActionInterface
{
    public function __construct(private Connection $connection, private StorageKeyGeneratorContract $storageKeyGenerator)
    {
    }

    public function persistRequest(FileReferencePersistRequestPayload $payload): FileReferencePersistRequestResult
    {
        $portalNodeKey = $payload->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new InvalidCreatePayloadException(
                $payload,
                1645822126,
                new UnsupportedStorageKeyException(\get_class($portalNodeKey))
            );
        }

        $result = new FileReferencePersistRequestResult($portalNodeKey);

        $this->connection->transactional(function (Connection $connection) use ($portalNodeKey, $payload, $result): void {
            $portalNodeId = Id::toBinary($portalNodeKey->getUuid());
            $now = DateTime::nowToStorage();

            $storageKeys = new \ArrayIterator(\iterable_to_array($this->storageKeyGenerator->generateKeys(
                FileReferenceRequestKeyInterface::class,
                \count($payload->getSerializedRequests())
            )));

            foreach ($payload->getSerializedRequests() as $key => $serializedRequest) {
                $storageKey = $storageKeys->current();
                $storageKeys->next();

                if (!$storageKey instanceof FileReferenceRequestStorageKey) {
                    throw new InvalidCreatePayloadException(
                        $payload,
                        1645822126,
                        new UnsupportedStorageKeyException(\get_class($storageKey))
                    );
                }

                $connection->insert('heptaconnect_file_reference_request', [
                    'id' => Id::toBinary($storageKey->getUuid()),
                    'portal_node_id' => $portalNodeId,
                    'serialized_request' => $serializedRequest,
                    'created_at' => $now,
                ], [
                    'id' => Type::BINARY,
                    'portal_node_id' => Type::BINARY,
                ]);

                $result->addFileReferenceRequestKey($key, $storageKey);
            }
        });

        return $result;
    }
}
