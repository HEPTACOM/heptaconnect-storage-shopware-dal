<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType;

use Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class DatasetEntityTypeEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
     */
    protected string $type = DatasetEntityInterface::class;

    protected ?MappingNodeCollection $mappingNodes = null;

    /**
     * @psalm-return class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface>
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @psalm-param class-string<\Heptacom\HeptaConnect\Dataset\Base\Contract\DatasetEntityInterface> $type
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
