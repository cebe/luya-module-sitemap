<p align="center">
  <img src="https://raw.githubusercontent.com/luyadev/luya/master/docs/logo/luya-logo-0.2x.png" alt="LUYA Logo"/>
</p>

# LUYA sitemap.xml Module


[![LUYA](https://img.shields.io/badge/Powered%20by-LUYA-brightgreen.svg)](https://luya.io)

The LUYA sitemap.xml module provides sitemap.xml support for SEO.

Currently it only generates sitemap entries for pages created by the CMS module.

> **Warning: Code is still in experimental state, so use with care!**

## Installation

For the installation of modules Composer is required.

```sh
composer require cebe/luya-module-sitemap:~0.9.0@alpha
```

### Configuration

Add the frontend module of the sitemap module to your configuration modules section:

```php
return [
    'modules' => [
        // ...
        'sitemap' => cebe\luya\sitemap\Module::class,
        // ...
    ],
];
```

> Please note that the module name *sitemap* is required and should not be changed!

## Testing

In order to run the unit tests install sqlite

```sh
sudo apt-get install php-sqlite3
```

and run the tests

```sh
./vendor/bin/phpunit tests/
```