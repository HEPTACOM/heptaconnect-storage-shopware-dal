<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\Portal\Contract\PortalContract;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ContextFactory;
use Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Repository\PortalNodeRepository;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\PortalStorage
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 */
class PortalStorageTest extends TestCase
{
    public function testUniqueNaming(): void
    {
        $contextFactory = new ContextFactory();
        $portalNodeRepository = new PortalNodeRepository($this->kernel->getContainer()->get('heptaconnect_portal_node.repository'), new StorageKeyGenerator(), $contextFactory);
        $storage = new PortalStorage($this->kernel->getContainer()->get('heptaconnect_portal_node_storage.repository'), $contextFactory);
        $portalNodeKey = $portalNodeRepository->create(PortalContract::class);

        $storage->set($portalNodeKey, 'foobar', 'foobar', 'string');
        $storage->set($portalNodeKey, 'FooBar', 'FooBar', 'string');
        $storage->set($portalNodeKey, 'foobar ', 'foobar ', 'string');
        $storage->set($portalNodeKey, 'FooBar ', 'FooBar ', 'string');

        self::assertSame('foobar', $storage->getValue($portalNodeKey, 'foobar'));
        self::assertSame('FooBar', $storage->getValue($portalNodeKey, 'FooBar'));
        self::assertSame('foobar ', $storage->getValue($portalNodeKey, 'foobar '));
        self::assertSame('FooBar ', $storage->getValue($portalNodeKey, 'FooBar '));
    }

    public function testHtmlLikeNaming(): void
    {
        $contextFactory = new ContextFactory();
        $portalNodeRepository = new PortalNodeRepository($this->kernel->getContainer()->get('heptaconnect_portal_node.repository'), new StorageKeyGenerator(), $contextFactory);
        $storage = new PortalStorage($this->kernel->getContainer()->get('heptaconnect_portal_node_storage.repository'), $contextFactory);
        $portalNodeKey = $portalNodeRepository->create(PortalContract::class);

        $storage->set($portalNodeKey, '<foobar>', '<foobar>', 'string');
        $storage->set($portalNodeKey, 'Foo<Bar', 'Foo<Bar', 'string');

        self::assertSame('<foobar>', $storage->getValue($portalNodeKey, '<foobar>'));
        self::assertSame('Foo<Bar', $storage->getValue($portalNodeKey, 'Foo<Bar'));
    }
}
