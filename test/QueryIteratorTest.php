<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator;

/**
 * @covers \Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Query\QueryIterator
 */
final class QueryIteratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $connection = $this->getConnection();
        $connection->executeStatement('CREATE TABLE storage_test_iterator (id INT AUTO_INCREMENT, PRIMARY KEY (id))');

        foreach (range(1, 50) as $_) {
            $connection->insert('storage_test_iterator', []);
        }
    }

    protected function tearDown(): void
    {
        $connection = $this->getConnection();

        if ($connection->getSchemaManager()->tablesExist('storage_test_iterator')) {
            $connection->getSchemaManager()->dropTable('storage_test_iterator');
        }

        parent::tearDown();
    }

    public function testSafeFetchSizeIsBiggerThanMaxResult(): void
    {
        $connection = $this->getConnection();
        $builder = $connection->createQueryBuilder();
        $builder->from('storage_test_iterator');
        $builder->select(['id']);
        $builder->setMaxResults(50);
        $builder->addOrderBy('id');

        $iterator = new QueryIterator();
        $rows = \iterable_to_array($iterator->iterateColumn($builder, 60));
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
        $builder->addOrderBy('id');

        $iterator = new QueryIterator();
        $queryCounts = [];

        foreach ($iterator->iterateColumn($builder, 3) as $_) {
            $queryCounts[] = \count($this->trackedQueries);
        }

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
        $builder->addOrderBy('id');

        $iterator = new QueryIterator();

        $rows = \iterable_to_array($iterator->iterateColumn($builder, 6));
        static::assertSame(\array_map('strval', \range(12, 19)), $rows);
        static::assertCount(2, $this->trackedQueries);
    }
}
