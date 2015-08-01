<?php

namespace Spinen\BrowserFilter;

use Illuminate\Support\ServiceProvider;
use hisorange\BrowserDetect\Provider\BrowserDetectService;

/**
 * Class FilterServiceProvider
 *
 * @package Spinen\BrowserFilter
 */
class FilterServiceProvider extends ServiceProvider
{
    /**
     * @var BrowserDetectService
     */
    protected $browser_detect;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->bootBrowserDetect();

        $config_file = realpath(__DIR__ . '/config/browserfilter.php');

        $this->publishes([
            $config_file => config_path('browserfilter.php'),
        ]);

        $this->mergeConfigFrom($config_file, 'browserfilter');
    }

    /**
     *  Preform post-registration booting of the Browser Detect service.
     */
    private function bootBrowserDetect()
    {
        $this->browser_detect->boot();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerBrowserDetect();
    }

    /**
     * Register the Browser Detect service.
     */
    private function registerBrowserDetect()
    {
        $this->browser_detect = $this->app->make(BrowserDetectService::class);

        $this->browser_detect->register();
    }
}
