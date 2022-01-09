<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class DalAccess
{
    public function queryValueById(
        EntityRepositoryInterface $repository,
        string $value,
        Criteria $criteria,
        Context $context
    ): array {
        $criteria = (clone $criteria)->addAggregation(new TermsAggregation(
            '_',
            'id',
            null,
            null,
            new TermsAggregation($value, $value),
        ));
        $aggregationResultCollection = $repository->aggregate($criteria, $context);
        /** @var TermsResult $aggregation */
        $aggregation = $aggregationResultCollection->get('_');
        $result = [];

        foreach ($aggregation->getBuckets() as $productIdBucket) {
            /** @var TermsResult $aggregationResult */
            $aggregationResult = $productIdBucket->getResult();

            foreach ($aggregationResult->getBuckets() as $productNumberBucket) {
                $result[$productIdBucket->getKey()] = $productNumberBucket->getKey();
            }
        }

        return $result;
    }
}
