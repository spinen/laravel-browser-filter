# SPINEN's Laravel Browser Filter

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

Build a page with named route to redirect blocked browsers to:

```php
    // This is only a simple example.  You would probably want to route to a controller with a view.
    Route::get('incompatible_browser', ['as' => 'incompatible_browser', 'uses' => function() {
        return "You are using a blocked browser.";
    }]);
```

## Configure middleware options

During the install process `config/browserfilter.php` as copied to the project.  That file is fully documented, so please read it to know how to configure the middleware.

There are 3 top level items that you can configure...

1. blocked - The array of devices/browsers/versions to block
2. route - The name of the route to redirect the user if they are using a blocked client
3. timeout - The length of time to cache the client
