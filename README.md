<p align="center">
  <img src="https://raw.githubusercontent.com/luyadev/luya/master/docs/logo/luya-logo-0.2x.png" alt="LUYA Logo"/>
</p>

# LUYA sitemap.xml Module


[![Latest Stable Version](https://poser.pugx.org/cebe/luya-module-sitemap/v/stable)](https://packagist.org/packages/cebe/luya-module-sitemap)
[![Build Status](https://travis-ci.org/cebe/luya-module-sitemap.svg?branch=master)](https://travis-ci.org/cebe/luya-module-sitemap)
[![License](https://poser.pugx.org/cebe/luya-module-sitemap/license)](https://packagist.org/packages/cebe/luya-module-sitemap)
[![LUYA](https://img.shields.io/badge/Powered%20by-LUYA-brightgreen.svg)](https://luya.io)

The LUYA sitemap.xml module provides sitemap.xml support for SEO.

Currently it only generates sitemap entries for pages created by the CMS module.


## Installation

For the installation of modules Composer is required.

    composer require cebe/luya-module-sitemap

### Configuration

Add the frontend module of the sitemap module to your configuration modules section:

```php
return [
    'modules' => [
        // ...
        'sitemap' => [
            'class' => cebe\luya\sitemap\Module::class,
            // available configuration options:

            // include hidden pages in sitemap.xml, default=false
            //'withHidden' => true,

            // encode urls in sitemap.xml, default=true
            //'encodeUrls' => true,
        ],
        // ...
    ],
];
```

> Please note that the module name *sitemap* is required and should not be changed!

## Development & Testing

In order to run the unit tests install sqlite

```sh
sudo apt-get install php-sqlite3
```

and run the tests

```sh
./vendor/bin/phpunit tests/
```

## Support

Professional support, consulting as well as software development services are available:

https://www.cebe.cc/en/contact

Development of this library is sponsored by [cebe.:cloud: "Your Professional Deployment Platform"](https://cebe.cloud).
