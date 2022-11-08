# A Laravel package for multi-source data aggregation and indexing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nihilsen/seeker.svg?style=flat-square)](https://packagist.org/packages/nihilsen/seeker)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/nihilsen/seeker/run-tests?label=tests)](https://github.com/nihilsen/seeker/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/nihilsen/seeker/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/nihilsen/seeker/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nihilsen/seeker.svg?style=flat-square)](https://packagist.org/packages/nihilsen/seeker)

This Laravel package provides a class-based framework for multi-source data aggregation and assimilation to Eloquent models.

## Installation

You can install the package via composer:

```bash
composer require nihilsen/seeker
```

You run the migrations with:

```bash
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="seeker-config"
```

This is the contents of the published config file:

```php
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
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/nihilsen/.github/blob/main/CONTRIBUTING.md) for details.

## Credits

-   [nihilsen](https://github.com/nihilsen)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
