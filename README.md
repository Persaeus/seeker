[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# A Laravel package for multi-source data aggregation and indexing

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nihilsen/seeker.svg?style=flat-square)](https://packagist.org/packages/nihilsen/seeker)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/nihilsen/seeker/run-tests?label=tests)](https://github.com/nihilsen/seeker/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/nihilsen/seeker/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/nihilsen/seeker/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/nihilsen/seeker.svg?style=flat-square)](https://packagist.org/packages/nihilsen/seeker)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/seeker.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/seeker)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

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
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="seeker-views"
```

## Usage

```php
$seeker = new Nihilsen\Seeker();
echo $seeker->echoPhrase('Hello, Nihilsen!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/nihilsen/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [nihilsen](https://github.com/nihilsen)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
