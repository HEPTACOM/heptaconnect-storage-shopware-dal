<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class PortalNodeStorageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_portal_node_storage';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return PortalNodeStorageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return PortalNodeStorageCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        // 4 times the size on the database to allow for utf8mb4 but with binary support
        $keyField = (new StringField('key', 'key', 1024))->addFlags(new Required());

        if (\class_exists(AllowHtml::class)) {
            $keyField->addFlags(new AllowHtml());
        }

        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            $keyField,
            (new BlobField('value', 'value'))->addFlags(new Required()),
            // 4 times the size on the database to allow for utf8mb4 but with binary support
            (new StringField('type', 'type', 255))->addFlags(new Required()),
            new DateTimeField('expired_at', 'expiredAt'),

            (new FkField('portal_node_id', 'portalNodeId', PortalNodeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('portalNode', 'portal_node_id', PortalNodeDefinition::class),
        ]);
    }
}
