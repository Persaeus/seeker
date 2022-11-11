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
    | Alternatively, if set to null, we attempt auto-loading from the
    | configured endpoints namespace (see below).
    |
    */

    'endpoints' => null,

    /*
    |--------------------------------------------------------------------------
    | Namespace
    |--------------------------------------------------------------------------
    |
    | Set the root namespace to associate with endpoint classes, for use in
    | endpoint auto-discovery.
    |
    */

    'namespace' => \App\Seeker\Endpoints::class,

    /*
    |--------------------------------------------------------------------------
    | Directory
    |--------------------------------------------------------------------------
    |
    | Set the root directory for use in endpoint auto-discovery.
    |
    | If set to null, we attempt to infer directory from the namespace.
    |
    */

    'directory' => null,

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
