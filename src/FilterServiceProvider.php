<?php

namespace Spinen\BrowserFilter;

use Illuminate\Support\ServiceProvider;

/**
 * Class FilterServiceProvider
 */
class FilterServiceProvider extends ServiceProvider
{
    /**
     * Location of the configuration file in the package
     */
    protected string $config_file;

    public function __construct($app)
    {
        parent::__construct($app);

        $this->config_file = realpath(__DIR__.'/config/browserfilter.php');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration file
        $this->publishes(
            [
                $this->config_file => $this->app['path.config'].DIRECTORY_SEPARATOR.'browserfilter.php',
            ]
        );
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Use default configuration
        $this->mergeConfigFrom($this->config_file, 'browserfilter');
    }
}
