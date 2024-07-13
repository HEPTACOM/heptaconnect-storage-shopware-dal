<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\PaginatableQueryBuilder;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryBuilderSortingDirection;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Id::class)]
#[CoversClass(PaginatableQueryBuilder::class)]
#[CoversClass(QueryIterator::class)]
final class QueryIteratorTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $connection->executeStatement('CREATE TABLE storage_test_iterator (id INT AUTO_INCREMENT, value VARCHAR(16) NULL, PRIMARY KEY (id))');
        $connection->beginTransaction();

        foreach (range(1, 25) as $_) {
            $connection->insert('storage_test_iterator', [
                'value' => null,
            ]);
        }

        foreach (range(1, 25) as $rowId) {
            $connection->insert('storage_test_iterator', [
                'value' => (string) $rowId,
            ]);
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        $connection = $this->getConnection();
        $connection->rollBack();

        if ($connection->getSchemaManager()->tablesExist('storage_test_iterator')) {
            $connection->getSchemaManager()->dropTable('storage_test_iterator');
        }

        $connection->beginTransaction();
        parent::tearDown();
    }

    public function testSafeFetchSizeIsBiggerThanMaxResult(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['id']);
        $builder->setMaxResults(50);

        $iterator = new QueryIterator();
        $rows = \iterable_to_array($iterator->iterateColumn($builder, 'id', QueryBuilderSortingDirection::ASCENDING, 60));
        static::assertSame(\array_map('strval', \range(1, 50)), $rows);
        static::assertCount(1, $this->trackedQueries);
    }

    public function testSafeFetchSizeIsSmallerThanMaxResultAndOnlyFetchesMaxResultEntries(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['id']);
        $builder->setMaxResults(8);

        $iterator = new QueryIterator();
        $queryCounts = [];

        foreach ($iterator->iterateColumn($builder, 'id', QueryBuilderSortingDirection::ASCENDING, 3) as $_) {
            $queryCounts[] = \count($this->trackedQueries ?? []);
        }

        // compare query count from each iteration. As fetch size is 3, the query count will only increase every 3 steps
        static::assertSame([
            1,
            1,
            1,
            2,
            2,
            2,
            3,
            3,
        ], $queryCounts);
    }

    public function testSafeFetchSizeIsSmallerFitsMultipleTimesInLimitAndOffsetAndFetchCorrectPage(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['id']);
        $builder->setFirstResult(11);
        $builder->setMaxResults(8);

        $iterator = new QueryIterator();

        $rows = \iterable_to_array($iterator->iterateColumn($builder, 'id', QueryBuilderSortingDirection::ASCENDING, 6));
        static::assertSame(\array_map('strval', \range(12, 19)), $rows);
        static::assertCount(2, $this->trackedQueries);
    }

    public function testSingleRowDetectsTooManyResults(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['id']);
        $builder->andWhere($builder->expr()->in('id', ':id1, :id2'));
        $builder->setParameter('id1', 1);
        $builder->setParameter('id2', 2);
        $builder->addOrderBy('id');

        $iterator = new QueryIterator();

        try {
            $iterator->fetchSingleValue($builder);
            static::fail();
        } catch (\LogicException $exception) {
            static::assertSame(1645901522, $exception->getCode());
        }
    }

    public function testNullValueDetectionWhenIteratingNullableStringColumn(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['value']);

        $iterator = new QueryIterator();

        try {
            $iterator->iterateColumn($builder, 'id');
            static::fail();
        } catch (\LogicException $exception) {
            static::assertSame(1719685570, $exception->getCode());
        }
    }

    public function testIteratingStringOnlyColumn(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['value']);
        $builder->where($builder->expr()->isNotNull('value'));

        $iterator = new QueryIterator();

        static::assertCount(25, \iterable_to_array($iterator->iterateColumn($builder, 'id')));
    }
}
