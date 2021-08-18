<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowEmptyString;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MappingDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_mapping';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MappingEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MappingCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        // 4 times the size on the database to allow for utf8mb4 but with binary support
        $externalId = new StringField('external_id', 'externalId', 512);

        if (\class_exists(AllowEmptyString::class)) {
            $externalId->addFlags(new AllowEmptyString());
        }

        if (\class_exists(AllowHtml::class)) {
            $externalId->addFlags(new AllowHtml());
        }

        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('portal_node_id', 'portalNodeId', PortalNodeDefinition::class))->addFlags(new Required()),
            (new FkField('mapping_node_id', 'mappingNodeId', MappingNodeDefinition::class))->addFlags(new Required()),
            $externalId,
            (new DateTimeField('deleted_at', 'deletedAt')),

            (new ManyToOneAssociationField('portalNode', 'portal_node_id', PortalNodeDefinition::class)),
            (new ManyToOneAssociationField('mappingNode', 'mapping_node_id', MappingNodeDefinition::class, 'id', true)),
        ]);
    }
}
