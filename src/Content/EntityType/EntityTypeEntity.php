<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EntityTypeEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>ø
     */
    protected string $type = DatasetEntityContract::class;

    protected ?MappingNodeCollection $mappingNodes = null;

    /**
     * @psalm-return class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract>
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityContract> $type
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMappingNodes(): ?MappingNodeCollection
    {
        return $this->mappingNodes;
    }

    public function setMappingNodes(?MappingNodeCollection $mappingNodes): self
    {
        $this->mappingNodes = $mappingNodes;

        return $this;
    }
}
