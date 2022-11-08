<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Endpoints
    |--------------------------------------------------------------------------
    |
    | Endpoint classes may be listed here as an array to control which should
    | be loaded.
    |
    | Alternatively, if set to null, we will attempt auto-loading from the
    | configured endpoints namespace (see below).
    |
    */

    'endpoints' => null,

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | Set the namespace to associate with endpoint classes.
    |
    */

    'namespace' => \App\Seeker\Endpoints::class,

    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Set the rate limiting middleware to use for Seek jobs.
    |
    | May be set to false or null to disable queue-level rate limiting.
    |
    | For optimized rate limiting using Redis, consider using
    | "Illuminate\Queue\Middleware\RateLimitedWithRedis::class",
    | as per <https://laravel.com/docs/9.x/queues#rate-limiting>
    |
    */

    'rate_limiter' => Illuminate\Queue\Middleware\RateLimited::class,

];
