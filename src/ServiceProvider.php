<?php

namespace Nihilsen\Seeker;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Nihilsen\Seeker\Jobs\Seek;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('seeker')
            ->hasConfigFile()
            ->hasMigrations(
                '0000_00_00_000001_create_queues_table',
                '0000_00_00_000002_create_endpoints_table',
                '0000_00_00_000003_create_responses_table',
                '0000_00_00_000004_create_data_table',
                '0000_00_00_000005_create_urls_table'
            )
            ->runsMigrations();
    }

    protected function configureRateLimiting()
    {
        if (! Config::rateLimiter()) {
            return;
        }

        RateLimiter::for(Seek::class, function (Seek $job) {
            $queue = $job->endpoint()->queue;
            if ($perMinute = $queue?->max_per_minute) {
                return Limit::perMinute($perMinute)->by($queue::class);
            }
        });
    }

    public function packageBooted()
    {
        $this->configureRateLimiting();
    }
}
