<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobEntity extends Entity
{
    use EntityIdTrait;

    protected string $externalId;

    protected string $portalNodeId;

    protected ?PortalNodeEntity $portalNode = null;

    protected string $entityTypeId;

    protected ?JobTypeEntity $entityType = null;

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

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): self
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getType(): ?JobTypeEntity
    {
        return $this->type;
    }

    public function setType(?JobTypeEntity $type): self
    {
        $this->type = $type;

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
