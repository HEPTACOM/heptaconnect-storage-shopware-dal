<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageCollection
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageDefinition
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Content\PortalNode\PortalNodeStorageEntity
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 */
class PortalStorageTest extends TestCase
{
    public function testUniqueNaming(): void
    {
        $contextFactory = new ContextFactory();
        $storage = new PortalStorage($this->kernel->getContainer()->get('heptaconnect_portal_node_storage.repository'), $contextFactory);
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNodeCreateAction = new PortalNodeCreate($connection, new StorageKeyGenerator());
        $portalNodeCreateResult = $portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKey = $portalNodeCreateResult[0]->getPortalNodeKey();

        $storage->set($portalNodeKey, 'foobar', 'foobar', 'string');
        $storage->set($portalNodeKey, 'FooBar', 'FooBar', 'string');
        $storage->set($portalNodeKey, 'foobar ', 'foobar ', 'string');
        $storage->set($portalNodeKey, 'FooBar ', 'FooBar ', 'string');

        static::assertSame('foobar', $storage->getValue($portalNodeKey, 'foobar'));
        static::assertSame('FooBar', $storage->getValue($portalNodeKey, 'FooBar'));
        static::assertSame('foobar ', $storage->getValue($portalNodeKey, 'foobar '));
        static::assertSame('FooBar ', $storage->getValue($portalNodeKey, 'FooBar '));
    }

    public function testHtmlLikeNaming(): void
    {
        $contextFactory = new ContextFactory();
        $storage = new PortalStorage($this->kernel->getContainer()->get('heptaconnect_portal_node_storage.repository'), $contextFactory);
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $portalNodeCreateAction = new PortalNodeCreate($connection, new StorageKeyGenerator());
        $portalNodeCreateResult = $portalNodeCreateAction->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(PortalContract::class),
        ]));
        $portalNodeKey = $portalNodeCreateResult[0]->getPortalNodeKey();

        $storage->set($portalNodeKey, '<foobar>', '<foobar>', 'string');
        $storage->set($portalNodeKey, 'Foo<Bar', 'Foo<Bar', 'string');

        static::assertSame('<foobar>', $storage->getValue($portalNodeKey, '<foobar>'));
        static::assertSame('Foo<Bar', $storage->getValue($portalNodeKey, 'Foo<Bar'));
    }
}
