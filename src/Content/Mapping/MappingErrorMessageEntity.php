<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class MappingErrorMessageEntity extends Entity
{
    use EntityIdTrait;

    protected string $mappingNodeId;

    protected ?string $previousId;

    protected ?string $groupPreviousId;

    protected ?string $message;

    protected ?string $stackTrace;

    protected ?MappingNodeEntity $mappingNode;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getMappingNodeId(): string
    {
        return $this->mappingNodeId;
    }

    public function setMappingNodeId(string $mappingNodeId): self
    {
        $this->mappingNodeId = $mappingNodeId;

        return $this;
    }

    public function getPreviousId(): ?string
    {
        return $this->previousId;
    }

    public function setPreviousId(?string $previousId): self
    {
        $this->previousId = $previousId;

        return $this;
    }

    public function getGroupPreviousId(): ?string
    {
        return $this->groupPreviousId;
    }

    public function setGroupPreviousId(?string $groupPreviousId): self
    {
        $this->groupPreviousId = $groupPreviousId;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getStackTrace(): ?string
    {
        return $this->stackTrace;
    }

    public function setStackTrace(?string $stackTrace): self
    {
        $this->stackTrace = $stackTrace;

        return $this;
    }

    public function getMappingNode(): ?MappingNodeEntity
    {
        return $this->mappingNode;
    }

    public function setMappingNode(?MappingNodeEntity $mappingNode): self
    {
        $this->mappingNode = $mappingNode;

        return $this;
    }
}
