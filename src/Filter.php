<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Spinen\BrowserFilter\Support\DecipherRules;
use Spinen\BrowserFilter\Support\ParserCreator;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter\Route
 */
abstract class Filter
{
    use DecipherRules;

    /**
     * The cache repository instance.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * The client instance.
     *
     * @var \UAParser\Result\Client
     */
    protected $client;

    /**
     * The config repository instance.
     *
     * @var Configs
     */
    protected $config;

    /**
     * Location of the config file.
     *
     * @var string
     */
    protected $config_path = 'browserfilter.';

    /**
     * The mobile detector instance.
     *
     * @var Mobile_Detect
     */
    protected $detector;

    /**
     * The path to redirect the user if client is blocked.
     *
     * @var string
     */
    protected $redirect_route;

    /**
     * The redirector instance.
     *
     * @var Redirector
     */
    protected $redirector;

    /**
     * The array of rules
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Create a new browser filter middleware instance.
     *
     * @param Cache         $cache      Cache
     * @param Config        $config     Config
     * @param Mobile_Detect $detector   Mobile_Detect
     * @param ParserCreator $parser     ParserCreator
     * @param Redirector    $redirector Redirector
     */
    public function __construct(
        Cache $cache,
        Config $config,
        Mobile_Detect $detector,
        ParserCreator $parser,
        Redirector $redirector
    ) {
        $this->cache = $cache;
        $this->config = $config;
        $this->detector = $detector;
        $this->client = $parser->parseAgent($this->detector->getUserAgent());
        $this->redirector = $redirector;
    }

    /**
     * Get the browsers being filtered.
     *
     * @return string|array
     */
    public function getBlockedBrowsers()
    {
        return ($this->haveRulesForDevice()) ? $this->getRules()[$this->client->device->family] : null;
    }

    /**
     * Get the versions of the browsers being filtered.
     *
     * @return string|array
     */
    public function getBlockedBrowserVersions()
    {
        return ($this->haveVersionsForBrowser())
            ? $this->getRules()[$this->client->device->family][$this->client->ua->family] : null;
    }

    /**
     * Get the route to the redirect path.
     *
     * @return string|null
     */
    public function getRedirectRoute()
    {
        return $this->redirect_route ?: $this->config->get($this->config_path . 'route');
    }

    /**
     * Return the array of rules.
     *
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request     $request        Request
     * @param Closure     $next           Closure
     * @param string|null $filter_string  Filter in string format
     * @param string|null $redirect_route Named route to redirect blocked client
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $filter_string = null, $redirect_route = null)
    {
        $this->redirect_route = $redirect_route;

        $this->parseFilterString($filter_string);

        // Delegate to the class that is extending this class, as to it knows what to do
        return $this->process($request, $next);
    }

    /**
     * Check to see if there are defined rules for the device.
     *
     * @return bool
     */
    protected function haveRulesForDevice()
    {
        return array_key_exists($this->client->device->family, $this->getRules());
    }

    /**
     * Check to see if there are defined versions for the browser for the device.
     *
     * @return bool
     */
    protected function haveVersionsForBrowser()
    {
        return array_key_exists($this->client->device->family, $this->getRules()) &&
               array_key_exists($this->client->ua->family, $this->getRules()[$this->client->device->family]);
    }

    /**
     * Delegate setting the rules from the passed in filter string.
     *
     * The the filter string will always be null on the stack filter.
     *
     * @param string $filter_string The filter(s)
     *
     * @return void
     */
    abstract public function parseFilterString($filter_string);

    /**
     * Delegate the processing of the filter to classes that know the logic that they need to preform.
     *
     * @param Request $request Request
     * @param Closure $next    Closure
     * @param array   $filter  The array of filters to check against the request
     *
     * @return mixed
     */
    abstract function process(Request $request, Closure $next, array $filter);
}
