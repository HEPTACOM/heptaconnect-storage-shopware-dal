<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobPayloadDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_job_payload';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return JobPayloadEntity::class;
    }

    public function getCollectionClass(): string
    {
        return JobPayloadCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new BlobField('payload', 'payload'))->addFlags(new Required()),
            (new StringField('format', 'format', 255))->addFlags(new Required()),
            (new StringField('checksum', 'checksum', 255))->addFlags(new Required()),
            new OneToManyAssociationField('jobs', JobDefinition::class, 'payload_id'),
        ]);
    }
}
