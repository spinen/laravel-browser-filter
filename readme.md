# SPINEN's Laravel Browser Filter

[![Latest Stable Version](https://poser.pugx.org/spinen/laravel-browser-filter/v/stable)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![Total Downloads](https://poser.pugx.org/spinen/laravel-browser-filter/downloads)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![Latest Unstable Version](https://poser.pugx.org/spinen/laravel-browser-filter/v/unstable)](https://packagist.org/packages/spinen/laravel-browser-filter)
[![Dependency Status](https://www.versioneye.com/php/spinen:laravel-browser-filter/0.1.1/badge.svg)](https://www.versioneye.com/php/spinen:laravel-browser-filter/0.1.1)
[![License](https://poser.pugx.org/spinen/laravel-browser-filter/license)](https://packagist.org/packages/spinen/laravel-browser-filter)

This is a Laravel 5 middleware to filter routes based on browser types.

We specify the browsers that we are going to support on the front of a project, so this package makes sure that the visitor is using a supported browser.

## Build Status

| Branch | Status | Coverage | Code Quality |
| ------ | :----: | :------: | :----------: |
| Develop | [![Build Status](https://travis-ci.org/spinen/laravel-browser-filter.svg?branch=develop)](https://travis-ci.org/spinen/laravel-browser-filter) | [![Coverage Status](https://coveralls.io/repos/spinen/laravel-browser-filter/badge.svg?branch=develop&service=github)](https://coveralls.io/github/spinen/laravel-browser-filter?branch=develop) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=develop) |
| Master | [![Build Status](https://travis-ci.org/spinen/laravel-browser-filter.svg?branch=master)](https://travis-ci.org/spinen/laravel-browser-filter) | [![Coverage Status](https://coveralls.io/repos/spinen/laravel-browser-filter/badge.svg?branch=master&service=github)](https://coveralls.io/github/spinen/laravel-browser-filter?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/spinen/laravel-browser-filter/?branch=master) |

## Prerequisite

As side from Laravel 5.x, there are 2 packages that are required

* [mobiledetect](https://github.com/serbanghita/Mobile-Detect) - To get the user agent string.  I know that this package is not need to get to the string, but there are other features that I plan on using in the future, so I kept it installed.
* [ua-parser PHP Library](https://github.com/tobie/ua-parser/tree/master/php) - To parse the user agent string

## Install

Install Browser Filter:

```bash
$ composer require spinen/laravel-browser-filter
```

Add the Service Provider to `config/app.php`:

```php
'providers' => [
    // ...
    Spinen\BrowserFilter\FilterServiceProvider::class,
];
```

Publish the package config file to `config/browserfilter.php`:

```bash
$ php artisan vendor:publish
```

Register the HTTP Stack Middleware in file `app/Http/Kernel.php`:

```php
    protected $middleware = [
        // ..
        \Spinen\BrowserFilter\Stack\Filter::class,
```

Register the Route Middlewares in file `app/Http/Kernel.php`:

```php
    protected $routeMiddleware = [
        // ..
        'browser.allow' => \Spinen\BrowserFilter\Route\AllowFilter::class,
        'browser.block' => \Spinen\BrowserFilter\Route\BlockFilter::class,
```

Build a page with named route to redirect blocked browsers to:

```php
    // This is only a simple example.  You would probably want to route to a controller with a view.
    Route::get('incompatible_browser', ['as' => 'incompatible_browser', 'uses' => function() {
        return "You are using a blocked browser.";
    }]);
```

## Configure middleware options

During the install process `config/browserfilter.php` as copied to the project.  That file is fully documented, so please read it to know how to configure the middleware.

There are 4 top level items that you can configure...

1. type - The type of filtering strategy to apply to the stack filter
2. rules - The array of devices/browsers/versions to allow/block for *ALL* http request
3. route - The name of the route to redirect the user if they are using a blocked client
4. timeout - The length of time to cache the client

## Using the Route middleware

The route middleware using the same configuration file as the stack middleware, but ignores the rules.

The rules are passed in after the ':' behind the router filter that you wish to use...

```php
    Route::get('tablet_page', [
        'middleware' => 'allowBrowser:Tablet',
        'uses'       => function () {
            return "Special page that is only accessible to tablets";
        }
    ]);
```

or 

```php
    Route::get('ie_is_blocked_page', [
        'middleware' => 'blockBrowser:Other/Ie',
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

would be written as `Mobile;Other/Ie/<10|>13;Tablet`.
