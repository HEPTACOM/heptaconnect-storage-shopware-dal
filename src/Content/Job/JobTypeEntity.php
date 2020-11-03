<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class JobTypeEntity extends Entity
{
    use EntityIdTrait;

    protected string $type;

    protected ?JobCollection $jobs = null;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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
