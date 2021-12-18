<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * @deprecated DAL usage is discouraged. Use route specific actions instead
 */
class JobEntity extends Entity
{
    use EntityIdTrait;

    protected string $externalId;

    protected string $portalNodeId;

    protected ?PortalNodeEntity $portalNode = null;

    protected string $entityTypeId;

    protected ?EntityTypeEntity $entityType = null;

    protected string $jobTypeId;

    protected ?JobTypeEntity $jobType = null;

    protected ?string $payloadId = null;

    protected ?JobPayloadEntity $payload = null;

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getPortalNodeId(): string
    {
        return $this->portalNodeId;
    }

    public function setPortalNodeId(string $portalNodeId): self
    {
        $this->portalNodeId = $portalNodeId;

        return $this;
    }

    public function getPortalNode(): ?PortalNodeEntity
    {
        return $this->portalNode;
    }

    public function setPortalNode(?PortalNodeEntity $portalNode): self
    {
        $this->portalNode = $portalNode;

        return $this;
    }

    public function getEntityTypeId(): string
    {
        return $this->entityTypeId;
    }

    public function setEntityTypeId(string $entityTypeId): self
    {
        $this->entityTypeId = $entityTypeId;

        return $this;
    }

    public function getEntityType(): ?EntityTypeEntity
    {
        return $this->entityType;
    }

    public function setEntityType(?EntityTypeEntity $entityType): self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getJobTypeId(): string
    {
        return $this->jobTypeId;
    }

    public function setJobTypeId(string $jobTypeId): self
    {
        $this->jobTypeId = $jobTypeId;

        return $this;
    }

    public function getJobType(): ?JobTypeEntity
    {
        return $this->jobType;
    }

    public function setJobType(?JobTypeEntity $jobType): self
    {
        $this->jobType = $jobType;

        return $this;
    }

    public function getPayloadId(): ?string
    {
        return $this->payloadId;
    }

    public function setPayloadId(?string $payloadId): self
    {
        $this->payloadId = $payloadId;

        return $this;
    }

    public function getPayload(): ?JobPayloadEntity
    {
        return $this->payload;
    }

    public function setPayload(?JobPayloadEntity $payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
