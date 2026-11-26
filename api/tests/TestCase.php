<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;
use LogicException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->assertSafePostgresDatabase($app);
        $this->assertSafeRedisDatabases($app);

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->clearRedisState();
        $this->beforeApplicationDestroyed(fn () => $this->clearRedisState());
    }

    private function assertSafePostgresDatabase(Application $app): void
    {
        $connection = $app->make('db')->connection();

        if (
            $connection->getDriverName() !== 'pgsql'
            || $connection->getDatabaseName() !== 'gymtracker_test'
        ) {
            throw new LogicException('Tests require the separate PostgreSQL database gymtracker_test.');
        }
    }

    private function assertSafeRedisDatabases(Application $app): void
    {
        $config = $app->make('config');

        foreach (['default' => 14, 'cache' => 15] as $name => $database) {
            $path = 'database.redis.'.$name;

            if (
                (string) $config->get($path.'.database') !== (string) $database
                || $config->get($path.'.host') !== 'redis'
                || (string) $config->get($path.'.port') !== '6379'
                || $config->get($path.'.url')
            ) {
                throw new LogicException('Tests require local Redis databases 14 and 15 without a Redis URL override.');
            }

            $client = $app->make('redis')->connection($name)->client();

            if (! $client instanceof \Redis || $client->getDBNum() !== $database) {
                throw new LogicException('Redis must select the reserved test database before cleanup.');
            }
        }
    }

    private function clearRedisState(): void
    {
        Redis::connection('default')->flushdb();
        Redis::connection('cache')->flushdb();
    }
}
