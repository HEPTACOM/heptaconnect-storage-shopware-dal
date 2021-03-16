<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\AllowHtml;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class MappingErrorMessageDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'heptaconnect_mapping_error_message';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return MappingErrorMessageEntity::class;
    }

    public function getCollectionClass(): string
    {
        return MappingErrorMessageCollection::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('mapping_id', 'mappingId', MappingDefinition::class))->addFlags(new Required()),
            new FkField('previous_id', 'previousId', self::class),
            new FkField('group_previous_id', 'groupPreviousId', self::class),
            (new StringField('type', 'type'))->addFlags(new Required()),
            (new LongTextField('message', 'message'))->addFlags(new AllowHtml()),
            // TODO: Add AllowEmptyString flag when it is supported
            (new LongTextField('stack_trace', 'stackTrace'))->addFlags(new AllowHtml()),

            new ManyToOneAssociationField('mapping', 'mapping_id', MappingDefinition::class),
        ]);
    }
}
