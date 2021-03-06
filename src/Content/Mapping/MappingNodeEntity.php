<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\MappingNodeStructInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * @psalm-suppress MissingConstructor
 * @psalm-suppress PropertyNotSetInConstructor
 */
class MappingNodeEntity extends Entity implements MappingNodeStructInterface
{
    use EntityIdTrait;

    protected string $typeId = '';

    protected string $originPortalNodeId = '';

    protected ?\DateTimeInterface $deletedAt = null;

    protected DatasetEntityTypeEntity $type;

    protected ?MappingCollection $mappings = null;

    protected ?PortalNodeEntity $originPortalNode = null;

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): self
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getOriginPortalNodeId(): string
    {
        return $this->originPortalNodeId;
    }

    public function setOriginPortalNodeId(string $originPortalNodeId): self
    {
        $this->originPortalNodeId = $originPortalNodeId;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getType(): DatasetEntityTypeEntity
    {
        return $this->type;
    }

    public function setType(DatasetEntityTypeEntity $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMappings(): ?MappingCollection
    {
        return $this->mappings;
    }

    public function setMappings(?MappingCollection $mappings): self
    {
        $this->mappings = $mappings;

        return $this;
    }

    public function getOriginPortalNode(): ?PortalNodeEntity
    {
        return $this->originPortalNode;
    }

    public function setOriginPortalNode(?PortalNodeEntity $originPortalNode): self
    {
        $this->originPortalNode = $originPortalNode;

        return $this;
    }

    public function getKey(): MappingNodeKeyInterface
    {
        return new MappingNodeStorageKey($this->id);
    }

    public function getDatasetEntityClassName(): string
    {
        return $this->getType()->getType();
    }
}
