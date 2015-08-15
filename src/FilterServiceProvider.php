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
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $config_file = realpath(__DIR__ . '/config/browserfilter.php');

        $this->publishes([
            $config_file => $this->app['path.config'] . DIRECTORY_SEPARATOR . 'browserfilter.php',
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
