<?php declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\WebhookKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\Webhook\Contract\WebhookInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\StorageKeyGeneratorContract;
use Heptacom\HeptaConnect\Storage\Base\Contract\WebhookRepositoryContract;
use Heptacom\HeptaConnect\Storage\Base\Exception\NotFoundException;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\WebhookStorageKey;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class WebhookRepository extends WebhookRepositoryContract
{
    private EntityRepositoryInterface $webhooks;

    private StorageKeyGeneratorContract $storageKeyGenerator;

    public function __construct(
        EntityRepositoryInterface $webhooks,
        StorageKeyGeneratorContract $storageKeyGenerator
    ) {
        $this->webhooks = $webhooks;
        $this->storageKeyGenerator = $storageKeyGenerator;
    }

    public function create(
        PortalNodeKeyInterface $portalNodeKey,
        string $url,
        string $handler,
        ?array $payload = null
    ): WebhookKeyInterface {
        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $key = $this->storageKeyGenerator->generateKey(WebhookKeyInterface::class);

        if (!$key instanceof WebhookStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $this->webhooks->create([[
            'id' => $key->getUuid(),
            'url' => $url,
            'handler' => $handler,
            'payload' => $payload,
            'portalNodeId' => $portalNodeKey->getUuid(),
        ]], Context::createDefaultContext());

        return $key;
    }

    public function read(WebhookKeyInterface $key): WebhookInterface
    {
        if (!$key instanceof WebhookStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($key));
        }

        $webhook = $this->webhooks->search(new Criteria([$key->getUuid()]), Context::createDefaultContext())->first();

        if (!$webhook instanceof WebhookInterface) {
            throw new NotFoundException();
        }

        return $webhook;
    }

    public function listByUrl(string $url): iterable
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('url', $url));
        $iterator = new RepositoryIterator($this->webhooks, Context::createDefaultContext(), $criteria);

        while (!empty($ids = $iterator->fetchIds())) {
            foreach ($ids as $id) {
                yield new WebhookStorageKey($id);
            }
        }
    }
}
