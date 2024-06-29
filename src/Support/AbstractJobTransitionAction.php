<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Support;

use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\JobKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\JobStorageKey;

abstract class AbstractJobTransitionAction
{
    /**
     * @return list<string>
     * @throws UnsupportedStorageKeyException
     */
    protected function getJobIds(JobKeyCollection $jobKeys): array
    {
        $jobIds = [];

        foreach ($jobKeys as $jobKey) {
            if (!$jobKey instanceof JobStorageKey) {
                throw new UnsupportedStorageKeyException(\get_debug_type($jobKey));
            }

            $jobIds[Id::toBinary($jobKey->getUuid())] = true;
        }

        return \array_keys($jobIds);
    }

    protected function packJobKeys(array $jobIds): JobKeyCollection
    {
        $result = new JobKeyCollection();

        foreach ($jobIds as $jobId) {
            $result->push([new JobStorageKey($jobId)]);
        }

        return $result;
    }
}
