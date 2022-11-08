<?php

namespace Nihilsen\Seeker\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Nihilsen\LaravelJoinUsing\ServiceProvider as LaravelJoinUsingServiceProvider;
use Nihilsen\Seeker\ServiceProvider;
use Nihilsen\Seeker\Tests\Endpoints\ComplexEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\ComplexSeedableEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleEndpoint;
use Nihilsen\Seeker\Tests\Endpoints\SimpleSeedableEndpoint;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelJoinUsingServiceProvider::class, // enable "join-using"
            ServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        Http::preventStrayRequests();

        config()->set([
            'database.default' => 'testing',
            'seeker' => [
                'endpoints' => [
                    SimpleEndpoint::class,
                    ComplexEndpoint::class,
                    SimpleSeedableEndpoint::class,
                    ComplexSeedableEndpoint::class,
                ],
                'namespace' => Endpoints::class,
            ],
        ]);
    }
}
