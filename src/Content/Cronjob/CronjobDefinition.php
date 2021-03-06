<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Cronjob;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * @internal
 */
class CronjobDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_cronjob';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return CronjobEntity::class;
    }

    public function getCollectionClass(): string
    {
        return CronjobCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('cron_expression', 'cronExpression'))->addFlags(new Required()),
            (new StringField('handler', 'handler'))->addFlags(new Required()),
            (new DateTimeField('queued_until', 'queuedUntil'))->addFlags(new Required()),
            new CustomFields('payload', 'payload'),

            (new FkField('portal_node_id', 'portalNodeId', PortalNodeDefinition::class))->addFlags(new Required()),
            new ManyToOneAssociationField('portalNode', 'portal_node_id', PortalNodeDefinition::class),

            new OneToManyAssociationField('copies', CronjobRunDefinition::class, 'copyFromId'),
        ]);
    }
}
