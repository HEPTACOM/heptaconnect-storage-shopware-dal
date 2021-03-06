<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\DatasetEntityType\DatasetEntityTypeDefinition;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MappingNodeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_mapping_node';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MappingNodeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MappingNodeCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('type_id', 'typeId', DatasetEntityTypeDefinition::class))->addFlags(new Required()),
            (new FkField('origin_portal_node_id', 'originPortalNodeId', PortalNodeDefinition::class))->addFlags(new Required()),
            (new DateTimeField('deleted_at', 'deletedAt')),

            (new ManyToOneAssociationField('type', 'type_id', DatasetEntityTypeDefinition::class, 'id', true)),
            (new OneToManyAssociationField('mappings', MappingDefinition::class, 'mapping_node_id', 'id')),
            (new ManyToOneAssociationField('originPortalNode', 'origin_portal_node_id', PortalNodeDefinition::class)),
        ]);
    }
}
