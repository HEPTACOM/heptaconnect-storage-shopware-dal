<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Bundle;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\DefinitionNotFoundException;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\EntityType\EntityTypeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\Mapping\MappingNodeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589662318CreateDatasetEntityTypeTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589673188CreateMappingNodeTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1589674916CreateMappingTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590070312CreateRouteTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1590250578CreateErrorMessageTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1595776348AddWebhookTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596457486AddCronjobTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596472471AddCronjobRunTable
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Migration\Migration1596939935CreatePortalNodeKeyValueStorageTable
 */
class ShopwareIntegrationTest extends TestCase
{
    protected Fixture\ShopwareKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new Fixture\ShopwareKernel();
        $this->kernel->boot();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testShopwareKernelLoading(): void
    {
        $this->kernel->registerBundles();
        $bundle = $this->kernel->getBundle('FixtureBundleForIntegration');

        static::assertInstanceOf(Bundle::class, $bundle);
    }

    /**
     * @depends testShopwareKernelLoading
     */
    public function testShopwareLoadingEntityRepositories(): void
    {
        /** @var DefinitionInstanceRegistry $definitionRegistration */
        $definitionRegistration = $this->kernel->getContainer()->get(DefinitionInstanceRegistry::class);

        try {
            $definition = $definitionRegistration->getByEntityName('heptaconnect_entity_type');
            static::assertEquals('heptaconnect_entity_type', $definition->getEntityName());
            static::assertEquals(EntityTypeCollection::class, $definition->getCollectionClass());
            static::assertEquals(EntityTypeEntity::class, $definition->getEntityClass());
            static::assertTrue($definition->getFields()->has('id'));
            static::assertTrue($definition->getFields()->has('type'));
            static::assertTrue($definition->getFields()->has('createdAt'));
            static::assertTrue($definition->getFields()->has('updatedAt'));
        } catch (DefinitionNotFoundException $e) {
            static::fail('Failed on loading heptaconnect_entity_type: '.$e->getMessage());
        }

        try {
            $definition = $definitionRegistration->getByEntityName('heptaconnect_mapping');
            static::assertEquals('heptaconnect_mapping', $definition->getEntityName());
            static::assertEquals(MappingCollection::class, $definition->getCollectionClass());
            static::assertEquals(MappingEntity::class, $definition->getEntityClass());
            static::assertTrue($definition->getFields()->has('id'));
            static::assertTrue($definition->getFields()->has('externalId'));
            static::assertTrue($definition->getFields()->has('portalNode'));
            static::assertTrue($definition->getFields()->has('portalNodeId'));
            static::assertTrue($definition->getFields()->has('mappingNode'));
            static::assertTrue($definition->getFields()->has('mappingNodeId'));
            static::assertTrue($definition->getFields()->has('createdAt'));
            static::assertTrue($definition->getFields()->has('updatedAt'));
            static::assertTrue($definition->getFields()->has('deletedAt'));
        } catch (DefinitionNotFoundException $e) {
            static::fail('Failed on loading heptaconnect_mappinge: '.$e->getMessage());
        }

        try {
            $definition = $definitionRegistration->getByEntityName('heptaconnect_mapping_node');
            static::assertEquals('heptaconnect_mapping_node', $definition->getEntityName());
            static::assertEquals(MappingNodeCollection::class, $definition->getCollectionClass());
            static::assertEquals(MappingNodeEntity::class, $definition->getEntityClass());
            static::assertTrue($definition->getFields()->has('id'));
            static::assertTrue($definition->getFields()->has('type'));
            static::assertTrue($definition->getFields()->has('typeId'));
            static::assertTrue($definition->getFields()->has('originPortalNode'));
            static::assertTrue($definition->getFields()->has('originPortalNodeId'));
            static::assertTrue($definition->getFields()->has('createdAt'));
            static::assertTrue($definition->getFields()->has('updatedAt'));
            static::assertTrue($definition->getFields()->has('deletedAt'));
        } catch (DefinitionNotFoundException $e) {
            static::fail('Failed on loading heptaconnect_mapping_node: '.$e->getMessage());
        }

        try {
            $definition = $definitionRegistration->getByEntityName('heptaconnect_portal_node');
            static::assertEquals('heptaconnect_portal_node', $definition->getEntityName());
            static::assertEquals(PortalNodeCollection::class, $definition->getCollectionClass());
            static::assertEquals(PortalNodeEntity::class, $definition->getEntityClass());
            static::assertTrue($definition->getFields()->has('id'));
            static::assertTrue($definition->getFields()->has('createdAt'));
            static::assertTrue($definition->getFields()->has('updatedAt'));
            static::assertTrue($definition->getFields()->has('deletedAt'));
        } catch (DefinitionNotFoundException $e) {
            static::fail('Failed on loading heptaconnect_portal_node: '.$e->getMessage());
        }
    }
}
