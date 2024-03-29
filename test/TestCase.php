<?php

declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Support\Id;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Shopware\Core\System\Language\CachedLanguageLoader;

abstract class TestCase extends BaseTestCase
{
    protected bool $setupKernel = true;

    protected bool $setupQueryTracking = true;

    protected ?ShopwareKernel $kernel = null;

    protected ?array $trackedQueries = null;

    private bool $performsDatabaseQueries = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->setupKernel) {
            $this->upKernel();

            if ($this->setupQueryTracking) {
                $this->performsDatabaseQueries = true;
                $this->upQueryTracking();
            }
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->setupKernel) {
            $this->downKernel();

            if ($this->setupQueryTracking) {
                $this->downQueryTracking();
            }
        }
    }

    protected function upKernel(): void
    {
        $this->kernel = new ShopwareKernel();
        $this->kernel->boot();
        $connection = $this->getConnection();

        $connection->beginTransaction();
        $connection->executeStatement('SET SESSION innodb_lock_wait_timeout = 5');

        /** @var CachedLanguageLoader $languageLoader */
        $languageLoader = $this->kernel->getContainer()->get(CachedLanguageLoader::class);
        $languageLoader->loadLanguages();
    }

    protected function downKernel(): void
    {
        $connection = $this->getConnection();
        $connection->getConfiguration()->setSQLLogger();
        $connection->rollBack();
        $this->kernel->shutdown();
        $connection->close();
    }

    protected function upQueryTracking(): void
    {
        $projectDir = \dirname(__DIR__) . '/';
        $this->trackedQueries = [];
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $pushQuery = fn () => $this->trackedQueries[] = \func_get_args();

        $connection->getConfiguration()->setSQLLogger(new class($pushQuery, $connection, $projectDir, $this) implements SQLLogger {
            /**
             * @var callable
             */
            private $track;

            private Connection $connection;

            private string $projectDir;

            private BaseTestCase $test;

            public function __construct($track, Connection $connection, string $projectDir, BaseTestCase $test)
            {
                $this->track = $track;
                $this->connection = $connection;
                $this->projectDir = $projectDir;
                $this->test = $test;
            }

            public function startQuery($sql, ?array $params = null, ?array $types = null): void
            {
                if (\stripos($sql, 'EXPLAIN') === 0 || \stripos($sql, 'SHOW WARNINGS') === 0) {
                    return;
                }

                if (\stripos($sql, 'INSERT INTO') === 0 && \stripos($sql, 'VALUES') !== false) {
                    return;
                }

                $rawFrames = \debug_backtrace();
                $startFrame = false;

                foreach ($rawFrames as $frameIndex => $rawFrame) {
                    $frameObject = $rawFrame['object'] ?? null;

                    if (!\is_object($frameObject)) {
                        continue;
                    }

                    if ($frameObject !== $this->test) {
                        continue;
                    }

                    $frameClass = $rawFrame['class'] ?? null;

                    if (!\is_string($frameClass)) {
                        continue;
                    }

                    if (!\str_starts_with($frameClass, 'PHPUnit\\Framework\\')) {
                        continue;
                    }

                    $startFrame = $frameIndex;

                    break;
                }

                if ($startFrame === false) {
                    return;
                }

                $frames = \array_map([$this, 'formatFrame'], \array_reverse(\array_slice($rawFrames, 2, $startFrame - 2)));

                // skip traces that only contain code from test cases and vendor folders
                if (\array_filter($frames, static fn (string $frame): bool => \str_contains($frame, ' (src/')) === []) {
                    return;
                }

                if ($frames === []) {
                    return;
                }

                $params ??= [];
                $types ??= [];
                $explainSql = 'EXPLAIN ' . $sql;
                $explanation = $this->connection->executeQuery($explainSql, $params, $types)->fetchAllAssociative();
                $warnings = $this->connection->executeQuery('SHOW WARNINGS')->fetchAllAssociative();
                $warnings = \array_diff_key($warnings, \array_keys(\array_column($warnings, 'Level'), 'Note', true));

                $call = $this->track;

                $call($sql, $params, $types, $explanation, $frames, $warnings);
            }

            public function stopQuery(): void
            {
            }

            private function formatFrame(array $frame): string
            {
                return \sprintf(
                    '%s%s%s (%s:%d)',
                    $frame['class'] ?? '',
                    $frame['type'] ?? '',
                    $frame['function'] ?? '',
                    \str_replace($this->projectDir, '', $frame['file'] ?? ''),
                    $frame['line'] ?? 0
                );
            }
        });
    }

    protected function downQueryTracking(): void
    {
        $trackedQueries = $this->trackedQueries;

        if ($this->performsDatabaseQueries) {
            static::assertNotEmpty($trackedQueries);
        }

        foreach ($trackedQueries as [$trackedQuery, $params, $types, $explanations, $frames, $warnings]) {
            foreach ($params as &$param) {
                try {
                    if (\is_array($param)) {
                        $param = \array_map(static fn (string $i): string => '0x' . Id::toHex($i), $param);
                    } else {
                        $param = '0x' . Id::toHex($param);
                    }
                } catch (\Throwable $throwable) {
                }
            }

            $context = \implode(\PHP_EOL, [
                '',
                $trackedQuery,
                \json_encode(['params' => $params], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
                \json_encode(['warnings' => $warnings], \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR),
                ...$frames,
            ]);

            if (\mb_stripos($trackedQuery, 'select') !== false) {
                static::assertStringContainsStringIgnoringCase('limit', $trackedQuery, 'Unlimited select found in ' . $context);
                static::assertStringContainsStringIgnoringCase('order by', $trackedQuery, 'Limited select without order by found in ' . $context);
            }

            foreach ($params as $param) {
                if (\is_array($param)) {
                    static::assertSame(
                        \array_values($param),
                        \array_values(\array_unique($param)),
                        'There is a duplicate value in an a parameter' . $context
                    );
                }
            }

            foreach ($explanations as $explanation) {
                $type = \strtolower($explanation['type'] ?? '');
                $extra = \strtolower($explanation['Extra'] ?? '');
                $explanationContext = \json_encode($explanation, \JSON_PRETTY_PRINT);

                if ($extra === 'no matching row in const table') {
                    continue;
                }

                // primary keys are unique, so a search in an index or in the index would both work by "using where"
                if ($type === 'all' && $extra === 'using where' && $explanation['possible_keys'] === 'PRIMARY') {
                    continue;
                }

                static::assertNotContains($type, ['all', 'fulltext'], 'Not indexed query found in ' . $explanationContext . \PHP_EOL . $context);
            }
        }

        $this->trackedQueries = [];
    }

    protected function getConnection(): Connection
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);

        return $connection;
    }

    protected function expectNotToPerformDatabaseQueries(): void
    {
        $this->performsDatabaseQueries = false;
    }
}
