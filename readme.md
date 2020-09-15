# SPINEN's Laravel Browser Filter

[![Latest Stable Version](https://poser.pugx.org/spinen/laravel-browser-filter/v/stable)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![Latest Unstable Version](https://poser.pugx.org/spinen/laravel-browser-filter/v/unstable)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![Total Downloads](https://poser.pugx.org/spinen/laravel-browser-filter/downloads)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![License](https://poser.pugx.org/spinen/laravel-browser-filter/license)](https://packagist.org/packages/spinen/laravel-browser-filter)

This is a Laravel 5 middleware to filter routes based on browser types.

We specify the browsers that we are going to support at the beginning of a project, so this package makes sure that the visitor is using a supported browser.

## Build Status

| Branch | Status | Coverage | Code Quality |
| ------ | :----: | :------: | :----------: |
| Develop | [![Build Status](https://github.com/spinen/laravel-browser-filter/workflows/CI/badge.svg?branch=develop)](https://github.com/spinen/laravel-browser-filter/workflows/CI/badge.svg?branch=develop) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=develop) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=develop) |
| Master | [![Build Status](https://github.com/spinen/laravel-browser-filter/workflows/CI/badge.svg?branch=master)](https://github.com/spinen/laravel-browser-filter/workflows/CI/badge.svg?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=master) |

## Prerequisites

#### NOTE: If you need to use PHP <7.2 or Laravel <5.2, please stay with version 1.x

As side from Laravel >= 5.5, there are 2 packages that are required:

* [mobiledetect](https://github.com/serbanghita/Mobile-Detect) - To get the user agent string. This package is not needed to get to the user agent string, but there are other features that I plan on using in the future so I kept it installed.
* [ua-parser PHP Library](https://github.com/ua-parser/uap-php) - To parse the user agent string

## Install

Install Browser Filter:

```bash
$ composer require spinen/laravel-browser-filter
```

The package uses the [auto registration feature](https://laravel.com/docs/5.8/packages#package-discovery) of Laravel 5.

```php
'providers' => [
    // ...
    Spinen\BrowserFilter\FilterServiceProvider::class,
];
```

## Register the middleware

The middleware needs to be registered with the Kernel to allow it to parse the request.

Register the HTTP Stack Middleware for the web group in `app/Http/Kernel.php`:

```php
    protected $middlewareGroups = [
        'web' => [
            // ..
            \Spinen\BrowserFilter\Stack\Filter::class,
        ],
        // ..
```

Register the Route Middlewares in `app/Http/Kernel.php`:

```php
    protected $routeMiddleware = [
        // ..
        'browser.allow' => \Spinen\BrowserFilter\Route\AllowFilter::class,
        'browser.block' => \Spinen\BrowserFilter\Route\BlockFilter::class,
```

Build a page with a named route to redirect blocked browsers to:

```php
    // This is only a simple example.  You would probably want to route to a controller with a view.
    Route::get('incompatible_browser', ['as' => 'incompatible_browser', 'uses' => function() {
        return "You are using a blocked browser.";
    }]);
```

## Configure middleware options

Publish the package config file to `config/browserfilter.php`:

```bash
$ php artisan vendor:publish --provider="Spinen\BrowserFilter\FilterServiceProvider"
```

This file is fully documented, so please read it to know how to configure the middleware.  There are 4 top level items that you can configure...

1. type - The type of filtering strategy to apply to the stack filter
2. rules - The array of devices/browsers/versions to allow or block for *ALL* http requests
3. route - The name of the route to redirect the user to if they are using a blocked client
4. timeout - The length of time to cache the client data, where "0" disables the cache

## Using the Route middleware

The route middleware uses the same configuration file as the stack middleware, but ignores the rules.

The rules are passed in after the ':' behind the route filter that you wish to use...

```php
    Route::get('tablet_page', [
        'middleware' => 'browser.allow:Tablet',
        'uses'       => function () {
            return "Special page that is only accessible to tablets";
        }
    ]);
```

or

```php
    Route::get('ie_is_blocked_page', [
        'middleware' => 'browser.block:Other/Ie',
        'uses'       => function () {
            return "Special page that is only accessible to non IE browsers on Desktops";
        }
    ]);
```

The format of the filter is `Device/Browser/operatorVersion|operatorVersion2;Device/Browser2/operatorVersion`, so the following rule:

```php
    $rule = [
        'Mobile' => '*',
        'Other' => [
            'Ie' => [
                '<' => '10',
                '>' => '13',
            ],
        ],
        'Tablet' => '*',
    ]
```

would be written as: `Mobile;Other/Ie/<10|>13;Tablet`.
