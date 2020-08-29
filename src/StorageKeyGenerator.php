<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\CronjobKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\MappingNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\StorageKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\CronjobStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\MappingNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey;
use Shopware\Core\Framework\Uuid\Uuid;

class StorageKeyGenerator extends StorageKeyGeneratorContract
{
    public function generateKey(string $keyClassName): StorageKeyInterface
    {
        if ($keyClassName === PortalNodeKeyInterface::class) {
            return $this->generatePortalNodeKey();
        }

        if ($keyClassName === MappingNodeKeyInterface::class) {
            return $this->generateMappingNodeKey();
        }

        if ($keyClassName === WebhookKeyInterface::class) {
            return $this->generateWebhookKey();
        }

        if ($keyClassName === CronjobKeyInterface::class) {
            return $this->generateCronjobKey();
        }

        throw new UnsupportedStorageKeyException($keyClassName);
    }

    private function generatePortalNodeKey(): PortalNodeStorageKey
    {
        return new PortalNodeStorageKey(Uuid::randomHex());
    }

    private function generateMappingNodeKey(): MappingNodeStorageKey
    {
        return new MappingNodeStorageKey(Uuid::randomHex());
    }

    private function generateWebhookKey(): WebhookStorageKey
    {
        return new WebhookStorageKey(Uuid::randomHex());
    }

    private function generateCronjobKey(): CronjobStorageKey
    {
        return new CronjobStorageKey(Uuid::randomHex());
    }
}
