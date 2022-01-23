<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingDefinition;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @deprecated DAL usage is discouraged. Use portal node specific actions instead
 */
class PortalNodeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_portal_node';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PortalNodeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PortalNodeCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            // 4 times the size on the database to allow for utf8mb4 but with binary support
            (new StringField('class_name', 'className', 255)),
            (new JsonField('configuration', 'configuration', [], []))->addFlags(new Required()),
            (new DateTimeField('deleted_at', 'deletedAt')),

            (new OneToManyAssociationField('mappings', MappingDefinition::class, 'portal_node_id', 'id')),
            (new OneToManyAssociationField('originalMappingNodes', MappingNodeDefinition::class, 'origin_portal_node_id', 'id')),
        ]);
    }
}
