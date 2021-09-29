<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Route;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\RouteInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\RouteKeyInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\RouteStorageKey;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class RouteEntity extends Entity implements RouteInterface
{
    use EntityIdTrait;

    protected string $typeId = '';

    protected string $sourceId = '';

    protected string $targetId = '';

    protected ?\DateTimeInterface $deletedAt = null;

    protected ?EntityTypeEntity $type = null;

    protected ?PortalNodeEntity $source = null;

    protected ?PortalNodeEntity $target = null;

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): RouteEntity
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function setSourceId(string $sourceId): RouteEntity
    {
        $this->sourceId = $sourceId;

        return $this;
    }

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    public function setTargetId(string $targetId): RouteEntity
    {
        $this->targetId = $targetId;

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

    public function getType(): ?EntityTypeEntity
    {
        return $this->type;
    }

    public function setType(?EntityTypeEntity $type): RouteEntity
    {
        $this->type = $type;

        return $this;
    }

    public function getSource(): ?PortalNodeEntity
    {
        return $this->source;
    }

    public function setSource(?PortalNodeEntity $source): RouteEntity
    {
        $this->source = $source;

        return $this;
    }

    public function getTarget(): ?PortalNodeEntity
    {
        return $this->target;
    }

    public function setTarget(?PortalNodeEntity $target): RouteEntity
    {
        $this->target = $target;

        return $this;
    }

    public function getKey(): RouteKeyInterface
    {
        return new RouteStorageKey($this->getId());
    }

    public function getTargetKey(): PortalNodeKeyInterface
    {
        return new PortalNodeStorageKey($this->getTargetId());
    }

    public function getSourceKey(): PortalNodeKeyInterface
    {
        return new PortalNodeStorageKey($this->getSourceId());
    }

    public function getEntityType(): string
    {
        return $this->getType()->getType();
    }
}
