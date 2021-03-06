<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Job;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeDefinition;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowEmptyString;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class JobDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_job';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return JobEntity::class;
    }

    public function getCollectionClass(): string
    {
        return JobCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        $externalId = new StringField('external_id', 'externalId', 512);

        if (\class_exists(AllowEmptyString::class)) {
            $externalId->addFlags(new AllowEmptyString());
        }

        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),

            $externalId,

            (new FkField('portal_node_id', 'portalNodeId', PortalNodeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('portalNode', 'portal_node_id', PortalNodeDefinition::class),

            (new FkField('entity_type_id', 'entityTypeId', DatasetEntityTypeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('entityType', 'entity_type_id', DatasetEntityTypeDefinition::class),

            (new FkField('job_type_id', 'jobTypeId', JobTypeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('jobType', 'job_type_id', JobTypeDefinition::class),

            (new FkField('payload_id', 'payloadId', PortalNodeDefinition::class)),
            new ManyToOneAssociationField('payload', 'payload_id', JobPayloadDefinition::class),
        ]);
    }
}
