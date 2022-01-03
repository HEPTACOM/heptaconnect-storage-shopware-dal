<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class EntityTypeDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_entity_type';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return EntityTypeEntity::class;
    }

    public function getCollectionClass(): string
    {
        return EntityTypeCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            // 4 times the size on the database to allow for utf8mb4 but with binary support
            (new StringField('type', 'type', 255))->addFlags(new Required()),

            (new OneToManyAssociationField('mappingNodes', MappingNodeDefinition::class, 'type_id', 'id')),
        ]);
    }
}
