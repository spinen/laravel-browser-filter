<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Spinen\BrowserFilter\Support\ParserCreator;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter\Route
 */
abstract class Filter
{
    /**
     * Is this a block or allow filter?
     *
     * @var bool
     */
    protected $blockFilter = null;

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
     * Determines if the client needs to be redirected.
     *
     * @return \Illuminate\Http\RedirectResponse|bool
     */
    protected function determineRedirect()
    {
        if ($this->needsRedirecting()) {
            return $this->redirector->route($this->getRedirectRoute());
        }

        return false;
    }

    /**
     * Get the browsers being filtered.
     *
     * @return string|array
     */
    public function getBrowsers()
    {
        return ($this->haveRulesForDevice()) ? $this->getRules()[$this->client->device->family] : null;
    }

    /**
     * Get the versions of the browsers being filtered.
     *
     * @return string|array
     */
    public function getBrowserVersions()
    {
        return ($this->haveVersionsForBrowser())
            ? $this->getRules()[$this->client->device->family][$this->client->ua->family] : null;
    }

    /**
     * Get the timeout of the cached value.
     *
     * @return mixed
     */
    protected function getCacheTimeout()
    {
        return $this->config->get($this->config_path . 'timeout');
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

        if ($this->onRedirectPath($request)) {
            return $next($request);
        }

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
     * Checks to see if the browser/client is blocked.
     *
     * @return bool
     */
    protected function isMatched()
    {
        return $this->isMatchedDevice() || $this->isMatchedBrowser() || $this->isMatchedBrowserVersion();
    }

    /**
     * Checks to see if all versions of the browser is blocked.
     *
     * @return bool
     */
    private function isMatchedBrowser()
    {
        return '*' === $this->getBrowserVersions();
    }

    /**
     * Checks to see if the version of the browser is blocked.
     *
     * Uses the php version_compare function to decide if there is a match.
     *
     * @link http://php.net/manual/en/function.version-compare.php
     *
     * @return bool
     */
    private function isMatchedBrowserVersion()
    {
        $denied = false;

        // cache it, so that we don't have to keep asking for it
        $client_version = $this->client->ua->toVersion();

        foreach ((array)$this->getBrowserVersions() as $operator => $version) {
            $denied |= (bool)version_compare($client_version, $version, $operator);
        }

        return $denied;
    }

    /**
     * Checks to see if all browsers of the device family is blocked.
     *
     * @return bool
     */
    private function isMatchedDevice()
    {
        return '*' === $this->getBrowsers();
    }

    /**
     * Decide if the client needs to be redirected.
     *
     * Here is the logic:
     *
     *   blockedFilter  true       true    false   false
     *   isMatched()    true       false   true    false
     *                  redirect   no      no      redirect
     *
     * so you can see this is a negative xor
     *
     * @return bool
     */
    protected function needsRedirecting()
    {
        return !$this->blockFilter xor $this->isMatched();
    }

    /**
     * Check to see if we are on the redirect page.
     *
     * If we did not test for this, then we would get into a redirect loop.
     *
     * @param Request $request Request
     *
     * @return bool
     */
    protected function onRedirectPath(Request $request)
    {
        // TODO: Move this to session flash data
        return $request->path() === $this->getRedirectRoute();
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
     *
     * @return mixed
     */
    abstract protected function process(Request $request, Closure $next);
}
