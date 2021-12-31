<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalExtension;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Activate\PortalExtensionActivateActionInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Activate\PortalExtensionActivatePayload;
use Heptacom\HeptaConnect\Storage\Base\Contract\Action\PortalExtension\Activate\PortalExtensionActivateResult;
use Heptacom\HeptaConnect\Storage\Base\Exception\UnsupportedStorageKeyException;
use Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\PortalNodeStorageKey;
use Ramsey\Uuid\Uuid;
use Shopware\Core\Defaults;

class PortalExtensionActivate implements PortalExtensionActivateActionInterface
{
    private Connection $connection;

    private ?QueryBuilder $selectQueryBuilder = null;

    private ?QueryBuilder $updateQueryBuilder = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function activate(PortalExtensionActivatePayload $payload): PortalExtensionActivateResult
    {
        $portalNodeKey = $payload->getPortalNodeKey();
        $payloadExtensions = $payload->getExtensions();

        if (!$portalNodeKey instanceof PortalNodeStorageKey) {
            throw new UnsupportedStorageKeyException(\get_class($portalNodeKey));
        }

        $portalNodeId = \hex2bin($portalNodeKey->getUuid());

        $existingExtensions = $this->getSelectQueryBuilder()
            ->setParameter('portalNodeId', $portalNodeId, Types::BINARY)
            ->setParameter('extensionClassNames', $payloadExtensions, Connection::PARAM_STR_ARRAY)
            ->execute()
            ->fetchAll(FetchMode::COLUMN) ?: [];

        $missingExtensions = \array_diff($payloadExtensions, $existingExtensions);

        $now = (new \DateTimeImmutable())->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        foreach ($missingExtensions as $missingExtension) {
            $this->connection->insert('heptaconnect_portal_node_extension', [
                'id' => Uuid::uuid4()->getBytes(),
                'portal_node_id' => $portalNodeId,
                'class_name' => (string) $missingExtension,
                'active' => 1,
                'created_at' => $now,
            ], [
                'id' => Types::BINARY,
                'portal_node_id' => Types::BINARY,
            ]);
        }

        $this->getUpdateQueryBuilder()
            ->setParameter('portalNodeId', $portalNodeId, Types::BINARY)
            ->setParameter('extensionClassNames', $payloadExtensions, Connection::PARAM_STR_ARRAY)
            ->execute();

        return new PortalExtensionActivateResult();
    }

    protected function getSelectQueryBuilder(): QueryBuilder
    {
        if (!$this->selectQueryBuilder instanceof QueryBuilder) {
            $this->selectQueryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->selectQueryBuilder->expr();

            $this->selectQueryBuilder
                ->select('portal_node_extension.class_name')
                ->from('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->where(
                    $expr->eq('portal_node_extension.portal_node_id', ':portalNodeId'),
                    $expr->in('portal_node_extension.class_name', ':extensionClassNames')
                );
        }

        return $this->selectQueryBuilder;
    }

    protected function getUpdateQueryBuilder(): QueryBuilder
    {
        if (!$this->updateQueryBuilder instanceof QueryBuilder) {
            $this->updateQueryBuilder = $this->connection->createQueryBuilder();
            $expr = $this->updateQueryBuilder->expr();

            $this->updateQueryBuilder
                ->update('heptaconnect_portal_node_extension', 'portal_node_extension')
                ->set('portal_node_extension.active', 1)
                ->where(
                    $expr->eq('portal_node_extension.portal_node_id', ':portalNodeId'),
                    $expr->in('portal_node_extension.class_name', ':extensionClassNames')
                );
        }

        return $this->updateQueryBuilder;
    }
}
