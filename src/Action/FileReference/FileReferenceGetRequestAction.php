<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\FileReference;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestResult;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\FileReference\FileReferenceGetRequestActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\FileReferenceRequestStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final readonly class FileReferenceGetRequestAction implements FileReferenceGetRequestActionInterface
{
    public const string FETCH_QUERY = '25e53ac0-de53-4039-a790-253fb5803fec';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function getRequest(FileReferenceGetRequestCriteria $criteria): iterable
    {
        $portalNodeKey = $criteria->getPortalNodeKey()->withoutAlias();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException($portalNodeKey);
        }

        $portalNodeId = Id::toBinary($portalNodeKey->getUuid());
        $requestIds = [];

        foreach ($criteria->getFileReferenceRequestKeys() as $requestKey) {
            if (!$requestKey instanceof FileReferenceRequestStorageKey) {
                throw new UnsupportedStorageKeyException($requestKey);
            }

            $requestIds[] = Id::toBinary($requestKey->getUuid());
        }

        $queryBuilder = $this->getQueryBuilder()
            ->setParameter('portalNodeKey', $portalNodeId, Types::BINARY)
            ->setParameter('requestIds', $requestIds, ArrayParameterType::STRING);

        /** @var array{request_id: string, serialized_request: string} $row */
        foreach ($queryBuilder->iterateRows('request.id') as $row) {
            yield new FileReferenceGetRequestResult(
                $portalNodeKey,
                new FileReferenceRequestStorageKey(Id::toHex($row['request_id'])),
                $row['serialized_request']
            );
        }
    }

    private function getQueryBuilder(): SelectQueryBuilder
    {
        $result = $this->queryFactory->createSelectBuilder(self::FETCH_QUERY);
        $expr = $result->expr();

        return $result
            ->select([
                'request.id request_id',
                'serialized_request',
            ])
            ->from('heptaconnect_file_reference_request', 'request')
            ->innerJoin(
                'request',
                'heptaconnect_portal_node',
                'portal_node',
                $expr->eq('portal_node.id', 'request.portal_node_id')
            )
            ->andWhere($expr->eq('request.portal_node_id', ':portalNodeKey'))
            ->andWhere($expr->isNull('portal_node.deleted_at'))
            ->andWhere($expr->in('request.id', ':requestIds'))
        ;
    }
}
