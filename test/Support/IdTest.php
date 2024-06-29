<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Support;

use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Id::class)]
final class IdTest extends TestCase
{
    #[DataProvider('generateValidHexId')]
    public function testBinaryHexConversion(string $hexId): void
    {
        $convertedIterable = [...Id::toHexIterable(Id::toBinaryIterable([$hexId]))];
        $convertedList = Id::toHexList(Id::toBinaryList([$hexId]));

        static::assertSame([$hexId], $convertedIterable);
        static::assertSame([$hexId], $convertedList);
    }

    #[DataProvider('generateValidHexIds')]
    public function testBinaryHexConversions(array $hexIds): void
    {
        $convertedIterable = [...Id::toHexIterable(Id::toBinaryIterable($hexIds))];
        $convertedList = Id::toHexList(Id::toBinaryList($hexIds));

        static::assertSame($hexIds, $convertedIterable);
        static::assertSame($hexIds, $convertedList);
    }

    public static function generateValidHexId(): iterable
    {
        // just a random value, any should be good
        yield 'static value 1' => ['f90991cf0fe146258a8c309f088a8397'];
        yield 'static value 2' => ['b49dab00f5ab48c58ffdb3865fec4f48'];
        yield 'static value 3' => ['36ff30b2f8364bb39cc8658a4965f5b2'];
        // now really have some random values
        yield 'generate value 1' => [Id::randomHex()];
        yield 'generate value 2' => [Id::randomHex()];
        yield 'generate value 3' => [Id::randomHex()];
    }

    public static function generateValidHexIds(): iterable
    {
        $result = [];

        foreach (static::generateValidHexId() as [$hexId]) {
            $result[] = $hexId;
        }

        yield 'all ids' => [$result];
    }
}
