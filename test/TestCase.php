<?php
declare(strict_types=1);

namespace Heptacom\HeptaConnect\Storage\ShopwareDal\Test;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\SQLLogger;
use Heptacom\HeptaConnect\Storage\ShopwareDal\Test\Fixture\ShopwareKernel;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Shopware\Core\System\Language\CachedLanguageLoader;

abstract class TestCase extends BaseTestCase
{
    protected bool $setupKernel = true;

    protected bool $setupQueryTracking = true;

    protected ?ShopwareKernel $kernel = null;

    protected ?array $trackedQueries = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->setupKernel) {
            $this->upKernel();

            if ($this->setupQueryTracking) {
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

        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
        $connection->beginTransaction();
        $connection->executeStatement('SET SESSION innodb_lock_wait_timeout = 5');

        /** @var CachedLanguageLoader $languageLoader */
        $languageLoader = $this->kernel->getContainer()->get(CachedLanguageLoader::class);
        $languageLoader->loadLanguages();
    }

    protected function downKernel(): void
    {
        /** @var Connection $connection */
        $connection = $this->kernel->getContainer()->get(Connection::class);
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

        $connection->getConfiguration()->setSQLLogger(new class ($pushQuery, $connection, $projectDir, static::class) implements SQLLogger {
            /**
             * @var callable
             */
            private $track;

            private Connection $connection;

            private string $projectDir;

            private string $parentClass;

            public function __construct($track, Connection $connection, string $projectDir, string $parentClass)
            {
                $this->track = $track;
                $this->connection = $connection;
                $this->projectDir = $projectDir;
                $this->parentClass = $parentClass;
            }

            public function startQuery($sql, ?array $params = null, ?array $types = null)
            {
                if (\stripos($sql, 'EXPLAIN') === 0 || \stripos($sql, 'SHOW WARNINGS') === 0) {
                    return;
                }

                $rawFrames = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
                $startFrame = \array_search($this->parentClass, \array_column($rawFrames, 'class'), true);

                if ($startFrame === false) {
                    return;
                }

                $frames = \array_map([$this, 'formatFrame'], \array_reverse(\array_slice($rawFrames, 2, -$startFrame)));
                $srcFrames = \array_filter($frames, static fn (string $f): bool => \stripos($f, ' (src/') !== false);

                if ($srcFrames === []) {
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

            public function stopQuery()
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

        foreach ($trackedQueries as [$trackedQuery, $params, $types, $explanations, $frames, $warnings]) {
            foreach ($explanations as $explanation) {
                $type = \strtolower($explanation['type'] ?? '');
                $context = \implode(\PHP_EOL, [
                    '',
                    $trackedQuery,
                    \json_encode($params, \JSON_PRETTY_PRINT),
                    \json_encode($explanation, \JSON_PRETTY_PRINT),
                    \json_encode($warnings, \JSON_PRETTY_PRINT),
                    ...$frames,
                ]);
                static::assertNotContains($type, ['all', 'fulltext'], 'Not indexed query found in ' . $context);

                $key = $explanation['key'] ?? null;
                $allKeys = $explanation['possible_keys'] ?? null;

                if ($key !== null && $allKeys !== null) {
                    static::assertStringContainsString($key, $allKeys, 'Unexpected key for query used in ' . $context);
                }
            }
        }

        $this->trackedQueries = [];
    }
}
