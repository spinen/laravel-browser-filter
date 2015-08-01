# SPINEN's base Laravel install

This is a Laravel 5 middleware to filter routes based on browser types.

We specify the browsers that we are going to support on the front of a project, so this package makes sure that the visitor is using a supported browser.

## Build Status

| Branch | Status |
| ------ | :----: |
| Develop | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-browser-filter-develop)](https://ci.spinen.net/view/Libraries/job/laravel-browser-filter-develop/) |
| Feature | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-browser-filter-feature__)](https://ci.spinen.net/view/Libraries/job/laravel-browser-filter-feature__/) |
| Master | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-browser-filter-master)](https://ci.spinen.net/view/Libraries/job/laravel-browser-filter-master/) |
| Release | [![Build Status](https://ci.spinen.net/buildStatus/icon?job=laravel-browser-filter-release__)](https://ci.spinen.net/view/Libraries/job/laravel-browser-filter-release__/) |

## Prerequisite

You have to have HiSoRange Browser Detect package installed

https://github.com/hisorange/browser-detect

## Install

Install Browser Filter:

```bash
$ composer require spinen/laravel-browser-filter
```

Add the Service Provider:

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

Register the HTTP Middleware in file `app/Http/Kernel.php`:

```php
    protected $middleware = [
        // ..
        Spinen\BrowserFilter\Filter::class,
```

Then edit `config/browserfilter.php` with correct versions of broswers.
