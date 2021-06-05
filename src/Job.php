<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\Mapping\Contract\MappingComponentStructContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\JobPayloadKeyInterface;

class Job implements JobInterface
{
    protected MappingComponentStructContract $mapping;

    protected string $jobType;

    protected ?JobPayloadKeyInterface $payloadKey;

    public function __construct(
        MappingComponentStructContract $mapping,
        string $jobType,
        ?JobPayloadKeyInterface $payloadKey
    ) {
        $this->mapping = $mapping;
        $this->jobType = $jobType;
        $this->payloadKey = $payloadKey;
    }

    public function getMapping(): MappingComponentStructContract
    {
        return $this->mapping;
    }

    public function getJobType(): string
    {
        return $this->jobType;
    }

    public function getPayloadKey(): ?JobPayloadKeyInterface
    {
        return $this->payloadKey;
    }
}
