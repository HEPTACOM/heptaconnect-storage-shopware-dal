<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\IdentityRedirect;

use Doctrine\DBAL\ArrayParameterType;
use Heptacom\HeptaConnect\Storage\Base\Action\IdentityRedirect\Delete\IdentityRedirectDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\IdentityRedirect\IdentityRedirectDeleteActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\IdentityRedirectStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\SelectQueryBuilder;

final readonly class IdentityRedirectDelete implements IdentityRedirectDeleteActionInterface
{
    public const string LOOKUP_QUERY = '26f18fa9-9246-45cf-b7f7-2fc80f61151d';

    public const string DELETE_QUERY = 'ca54ecac-3b6b-4f54-882e-fea1f19336ba';

    public function __construct(
        private QueryFactory $queryFactory
    ) {
    }

    #[\Override]
    public function delete(IdentityRedirectDeleteCriteria $criteria): void
    {
        $ids = [];

        foreach ($criteria->getIdentityRedirectKeys() as $identityRedirectKey) {
            if (!$identityRedirectKey instanceof IdentityRedirectStorageKey) {
                throw new UnsupportedStorageKeyException($identityRedirectKey);
            }

            $ids[] = Id::toBinary($identityRedirectKey->getUuid());
        }

        if ($ids === []) {
            return;
        }

        $searchBuilder = $this->getSearchQuery();
        $searchBuilder->setParameter('ids', $ids, ArrayParameterType::STRING);
        $foundIds = \iterable_to_array($searchBuilder->iterateColumn('id'));

        foreach ($ids as $id) {
            if (!\in_array($id, $foundIds, true)) {
                throw new NotFoundException();
            }
        }

        $deleteBuilder = $this->getDeleteQuery();
        $deleteBuilder->setParameter('ids', $ids, ArrayParameterType::STRING);
        $deleteBuilder->executeStatement();
    }

    private function getDeleteQuery(): QueryBuilder
    {
        $builder = $this->queryFactory->createBuilder(self::DELETE_QUERY);

        $builder->delete('heptaconnect_identity_redirect');
        $builder->andWhere($builder->expr()->in('id', ':ids'));

        return $builder;
    }

    private function getSearchQuery(): SelectQueryBuilder
    {
        $builder = $this->queryFactory->createSelectBuilder(self::LOOKUP_QUERY);

        $builder->from('heptaconnect_identity_redirect');
        $builder->select('id');
        $builder->andWhere($builder->expr()->in('id', ':ids'));

        return $builder;
    }
}
