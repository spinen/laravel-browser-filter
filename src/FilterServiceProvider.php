<?php

namespace Spinen\BrowserFilter;

use Illuminate\Support\ServiceProvider;

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
