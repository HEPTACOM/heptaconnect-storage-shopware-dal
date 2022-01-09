<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\MockObject\MockObject;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\ConfigurationStorage
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 */
class ConfigurationStorageTest extends TestCase
{
    public function testSetConfiguration(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('set')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                ),
                static::logicalAnd(
                    static::isType(IsType::TYPE_ARRAY),
                    static::arrayHasKey('foo')
                )
            );

        $storage = new ConfigurationStorage($systemConfigService);
        $storage->setConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'), ['foo' => 'bar']);
    }

    public function testGetConfiguration(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                )
            )
            ->willReturn(['foo' => 'bar']);

        $storage = new ConfigurationStorage($systemConfigService);
        $result = $storage->getConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'));
        static::assertEquals(['foo' => 'bar'], $result);
    }

    public function testGetConfigurationNonArray(): void
    {
        /** @var SystemConfigService&MockObject $systemConfigService */
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects(static::once())
            ->method('get')
            ->with(
                static::logicalAnd(
                    static::stringContains('2281f7b9f4e847d5b0bc084288b871b1'),
                    static::logicalNot(static::equalTo('2281f7b9f4e847d5b0bc084288b871b1'))
                )
            )
            ->willReturn('foobar');

        $storage = new ConfigurationStorage($systemConfigService);
        $result = $storage->getConfiguration(new PortalNodeStorageKey('2281f7b9f4e847d5b0bc084288b871b1'));
        static::assertEquals(['value' => 'foobar'], $result);
    }

    public function testResetStorage(): void
    {
        $systemConfigService = $this->kernel->getContainer()->get(SystemConfigService::class);
        $storage = new ConfigurationStorage($systemConfigService);
        $keyGenerator = new StorageKeyGenerator();

        /** @var PortalNodeKeyInterface $portalNodeKey */
        $portalNodeKey = $keyGenerator->generateKey(PortalNodeKeyInterface::class);
        $storage->setConfiguration($portalNodeKey, ['test' => true]);
        $value = $storage->getConfiguration($portalNodeKey);
        static::assertArrayHasKey('test', $value);
        static::assertTrue($value['test']);
        $storage->setConfiguration($portalNodeKey, null);
        static::assertCount(0, $storage->getConfiguration($portalNodeKey));
    }
}
