<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\ConfigurationStorageContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\Base\PreviewPortalNodeKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigurationStorage extends ConfigurationStorageContract
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getConfiguration(PortalNodeKeyInterface $portalNodeKey): array
    {
        if ($portalNodeKey instanceof PreviewPortalNodeKey) {
            return [];
        }

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        return $this->getConfigurationInternal($portalNodeKey->getUuid());
    }

    public function setConfiguration(PortalNodeKeyInterface $portalNodeKey, ?array $data): void
    {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $config = null;

        if (!\is_null($data)) {
            $value = $this->getConfigurationInternal($portalNodeKey->getUuid());
            $config = \array_replace_recursive($value, $data);
        }

        $this->systemConfigService->set($this->buildConfigurationPrefix($portalNodeKey->getUuid()), $config);
    }

    private function buildConfigurationPrefix(string $portalNodeId): string
    {
        return \sprintf('heptacom.heptaConnect.portalNodeConfiguration.%s', $portalNodeId);
    }

    private function getConfigurationInternal(string $portalNodeId): array
    {
        /** @var mixed|array|null $value */
        $value = $this->systemConfigService->get($this->buildConfigurationPrefix($portalNodeId));

        if (\is_null($value)) {
            return [];
        }

        if (\is_array($value)) {
            return $value;
        }

        return ['value' => $value];
    }
}
