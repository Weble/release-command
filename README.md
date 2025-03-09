# Laravel Release Command using Git Flow

[![Latest Version on Packagist](https://img.shields.io/packagist/v/weble/release-command.svg?style=flat-square)](https://packagist.org/packages/weble/release-command)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/weble/release-command/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/weble/release-command/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/weble/release-command/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/weble/release-command/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/weble/release-command.svg?style=flat-square)](https://packagist.org/packages/weble/release-command)

Quickly release by relying on git flow and tagging a new relase.

```php artisan release```

## Installation

You can install the package via composer --dev:

```bash
composer require weble/release-command --dev
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="release-command-config"
```

This is the contents of the published config file:

```php
<?php

return [

    /**
     * Path or name of the git executable
     */
    'git_bin' => 'git',

    /**
     * Which version to bump by default
     * Can be 'patch', 'minor', 'major'
     */
    'default_version_bump' => 'patch',

    /**
     * Name of the git remote where we need to push (default to 'origin')
     */
    'git_remote_name' => 'origin',

    /**
     * Push to origin default
     */
    'push_to_origin' => true,
];

```

## Usage

```php
php artisan release
```

or with a specific version

```php
php artisan release --release-version=1.2.3
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Weble](https://github.com/Weble)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
