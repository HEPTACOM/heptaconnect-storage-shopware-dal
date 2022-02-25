<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\FileReference;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferenceGetRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;

class FileReferenceGetRequestAction implements FileReferenceGetRequestActionInterface
{
    private Connection $connection;

    private ?QueryBuilder $queryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getRequest(FileReferenceGetRequestCriteria $criteria): iterable
    {
        $portalNodeKey = $criteria->getPortalNodeKey();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = \hex2bin($portalNodeKey->getUuid());
        $requestIds = [];

        foreach ($criteria->getFileReferenceRequestKeys() as $requestKey) {
            if (!$requestKey instanceof FileReferenceRequestStorageKey) {
                throw new UnsupportedStorageKeyException(\get_class($requestKey));
            }

            $requestIds[] = \hex2bin($requestKey->getUuid());
        }

        $queryBuilder = $this->getQueryBuilder()
            ->setParameter('portalNodeKey', $portalNodeId, Type::BINARY)
            ->setParameter('requestIds', $requestIds, Connection::PARAM_STR_ARRAY);

        foreach ($queryBuilder->execute()->fetchAllAssociative() as $fileReferenceRequest) {
            $requestId = \bin2hex($fileReferenceRequest['id']);
            $serializedRequest = (string) $fileReferenceRequest['serialized_request'];

            yield new FileReferenceGetRequestResult(
                $portalNodeKey,
                new FileReferenceRequestStorageKey($requestId),
                $serializedRequest
            );
        }
    }

    private function getQueryBuilder(): QueryBuilder
    {
        if (!$this->queryBuilder instanceof QueryBuilder) {
            $this->queryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->queryBuilder->expr();

            $this->queryBuilder
                ->select(['id', 'serialized_request'])
                ->from('heptaconnect_file_reference_request', 'request')
                ->andWhere($expr->eq('request.portal_node_id', ':portalNodeKey'))
                ->andWhere($expr->in('request.id', ':requestIds'));
        }

        return clone $this->queryBuilder;
    }
}
