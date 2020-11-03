<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobPayloadEntity extends Entity
{
    use EntityIdTrait;

    protected string $payload;

    protected string $format;

    protected string $checksum;

    protected ?JobCollection $jobs = null;

    public function getPayload(): string
    {
        return $this->payload;
    }

    public function setPayload(string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): self
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getJobs(): ?JobCollection
    {
        return $this->jobs;
    }

    public function setJobs(?JobCollection $jobs): self
    {
        $this->jobs = $jobs;

        return $this;
    }
}
