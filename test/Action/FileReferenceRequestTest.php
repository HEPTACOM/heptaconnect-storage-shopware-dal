<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Action;

use Heptacom\HeptaConnect\Portal\Base\StorageKey\Contract\PortalNodeKeyInterface;
use Heptacom\HeptaConnect\Portal\Base\StorageKey\PortalNodeKeyCollection;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestCriteria;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestGet\FileReferenceGetRequestResult;
use Heptacom\HeptaConnect\Storage\Base\Action\FileReference\RequestPersist\FileReferencePersistRequestPayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayload;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Create\PortalNodeCreatePayloads;
use Heptacom\HeptaConnect\Storage\Base\Action\PortalNode\Delete\PortalNodeDeleteCriteria;
use Heptacom\HeptaConnect\Storage\Base\Bridge\Contract\StorageFacadeInterface;
use Heptacom\HeptaConnect\Storage\Base\Contract\FileReferenceRequestKeyInterface;
use Heptacom\HeptaConnect\Storage\Base\FileReferenceRequestKeyCollection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\Portal\Portal;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\TestCase;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\FileReference\FileReferenceGetRequestAction
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\FileReference\FileReferencePersistRequestAction
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeCreate
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Action\PortalNode\PortalNodeDelete
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Bridge\StorageFacade
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKey\AbstractStorageKey
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\StorageKeyGenerator
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\DateTime
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilder
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryFactory
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
final class FileReferenceRequestTest extends TestCase
{
    public function testRetrieval(): void
    {
        $facade = $this->getStorageFacade();
        $portalNodeCreate = $facade->getPortalNodeCreateAction();
        $portalNodeDelete = $facade->getPortalNodeDeleteAction();
        $persist = $facade->getFileReferencePersistRequestAction();
        $get = $facade->getFileReferenceGetRequestAction();

        /** @var PortalNodeKeyInterface $portalNodeKey */
        $portalNodeKey = $portalNodeCreate->create(new PortalNodeCreatePayloads([
            new PortalNodeCreatePayload(Portal::class),
        ]))->first()->getPortalNodeKey();

        $bigPayload = \str_repeat('646f1c8e-D735-4590-af52<21e30242389b ', 100);

        $persistPayload = new FileReferencePersistRequestPayload($portalNodeKey);
        $persistPayload->addSerializedRequest('request-a', 'php');
        $persistPayload->addSerializedRequest('request-b', 'is');
        $persistPayload->addSerializedRequest('request-c', 'nice');
        $persistPayload->addSerializedRequest('request-d', $bigPayload);

        static::assertArrayHasKey('request-a', $persistPayload->getSerializedRequests());
        static::assertArrayHasKey('request-b', $persistPayload->getSerializedRequests());
        static::assertArrayHasKey('request-c', $persistPayload->getSerializedRequests());
        static::assertArrayHasKey('request-d', $persistPayload->getSerializedRequests());

        $persistResult = $persist->persistRequest($persistPayload);

        static::assertTrue($persistResult->getPortalNodeKey()->equals($portalNodeKey));
        static::assertSame(['request-a', 'request-b', 'request-c', 'request-d'], \array_keys($persistResult->getFileReferenceRequestKeys()));

        $keyA = $persistResult->getFileReferenceRequestKey('request-a');
        $keyB = $persistResult->getFileReferenceRequestKey('request-b');
        $keyC = $persistResult->getFileReferenceRequestKey('request-c');
        $keyD = $persistResult->getFileReferenceRequestKey('request-d');

        static::assertInstanceOf(FileReferenceRequestKeyInterface::class, $keyA);
        static::assertInstanceOf(FileReferenceRequestKeyInterface::class, $keyB);
        static::assertInstanceOf(FileReferenceRequestKeyInterface::class, $keyC);
        static::assertInstanceOf(FileReferenceRequestKeyInterface::class, $keyD);

        $getCriteria = new FileReferenceGetRequestCriteria($portalNodeKey, new FileReferenceRequestKeyCollection([
            $keyA,
            $keyD,
        ]));

        /** @var FileReferenceGetRequestResult[] $getResults */
        $getResults = \iterable_to_array($get->getRequest($getCriteria));

        static::assertCount(2, $getResults);

        $matches = [];

        foreach ($getResults as $getResult) {
            static::assertTrue($portalNodeKey->equals($getResult->getPortalNodeKey()));

            $matches[$getResult->getSerializedRequest()] = $getResult->getRequestKey();
        }

        static::assertCount(2, $matches);
        static::assertArrayHasKey('php', $matches);
        static::assertArrayHasKey($bigPayload, $matches);
        static::assertTrue($keyA->equals($matches['php']));
        static::assertTrue($keyD->equals($matches[$bigPayload]));

        $portalNodeDelete->delete(new PortalNodeDeleteCriteria(new PortalNodeKeyCollection([$portalNodeKey])));

        /** @var FileReferenceGetRequestResult[] $getResults */
        $getResults = \iterable_to_array($get->getRequest($getCriteria));

        static::assertEmpty($getResults);
    }

    protected function getStorageFacade(): StorageFacadeInterface
    {
        return new StorageFacade($this->getConnection());
    }
}
