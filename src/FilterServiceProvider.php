<?php

namespace Spinen\BrowserFilter;

use Illuminate\Support\ServiceProvider;

/**
 * Class FilterServiceProvider
 *
 * @package Spinen\BrowserFilter
 */
class FilterServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var boolean
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $config_file = realpath(__DIR__ . '/config/browserfilter.php');

        $this->publishes([
            $config_file => config_path('browserfilter.php'),
        ]);

        $this->mergeConfigFrom($config_file, 'browserfilter');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
