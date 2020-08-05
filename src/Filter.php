<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Spinen\BrowserFilter\Exceptions\FilterTypeNotSetException;
use Spinen\BrowserFilter\Exceptions\InvalidRuleDefinitionsException;
use Spinen\BrowserFilter\Support\ParserCreator;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter
 */
abstract class Filter
{
    /**
     * Is this a block or allow filter?
     *
     * @var bool
     */
    protected $block_filter = null;

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
     * @var Config
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
     * @param Cache $cache Cache
     * @param Config $config Config
     * @param Mobile_Detect $detector Mobile_Detect
     * @param ParserCreator $parser ParserCreator
     * @param Redirector $redirector Redirector
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
     * @return string|bool
     */
    public function determineRedirect()
    {
        if ($this->needsRedirecting()) {
            return $this->getRedirectRoute();
        }

        return false;
    }

    /**
     * Generate the key to use to cache the determination.
     *
     * @param Request $request
     *
     * @return string
     */
    public function generateCacheKey(Request $request)
    {
        // NOTE: $request is an unused variable here, but needed in a class that extends this one
        return $this->client->device->family . ':' . $this->client->ua->family . ':' . $this->client->ua->toVersion();
    }

    /**
     * Get the browsers being filtered.
     *
     * @return string|array
     */
    public function getBrowsers()
    {
        return $this->haveRulesForDevice() ? $this->getRules()[$this->client->device->family] : null;
    }

    /**
     * Get the versions of the browsers being filtered.
     *
     * @return string|array|null
     */
    public function getBrowserVersions()
    {
        if ($this->haveVersionsForBrowser()) {
            return $this->getRules()[$this->client->device->family][$this->client->ua->family];
        }

        return null;
    }

    /**
     * Get the timeout of the cached value.
     *
     * @return mixed
     */
    public function getCacheTimeout()
    {
        return $this->config->get($this->config_path . 'timeout');
    }

    /**
     * Return the filter type.
     *
     * @return string
     *
     * @throws FilterTypeNotSetException
     */
    public function getFilterType()
    {
        if (is_bool($this->block_filter)) {
            return $this->block_filter ? 'block' : 'allow';
        }

        throw new FilterTypeNotSetException();
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
     * @param Request $request Request
     * @param Closure $next Closure
     * @param string|null $filter_string Filter in string format
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

        $cache_key = $this->generateCacheKey($request);

        $redirect = $this->cache->get($cache_key);

        if (is_null($redirect)) {
            $this->parseFilterString($filter_string);

            $this->validateRules();

            $redirect = $this->determineRedirect();

            $this->cache->put($cache_key, $redirect, $this->getCacheTimeout());
        }

        if ($redirect) {
            $request->session()
                    ->flash('redirected', true);

            return $this->redirector->route($redirect);
        }

        return $next($request);
    }

    /**
     * Check to see if there are defined rules for the device.
     *
     * @return bool
     */
    public function haveRulesForDevice()
    {
        return array_key_exists($this->client->device->family, $this->getRules());
    }

    /**
     * Check to see if there are defined versions for the browser for the device.
     *
     * @return bool
     */
    public function haveVersionsForBrowser()
    {
        return array_key_exists($this->client->device->family, $this->getRules()) &&
            array_key_exists($this->client->ua->family, $this->getRules()[$this->client->device->family]);
    }

    /**
     * Checks to see if the browser/client is blocked.
     *
     * @return bool
     */
    public function isMatched()
    {
        return $this->isMatchedDevice() || $this->isMatchedBrowser() || $this->isMatchedBrowserVersion();
    }

    /**
     * Checks to see if all versions of the browser is blocked.
     *
     * @return bool
     */
    public function isMatchedBrowser()
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
    public function isMatchedBrowserVersion()
    {
        $denied = false;

        // cache it, so that we don't have to keep asking for it
        $client_version = $this->client->ua->toVersion();

        foreach ((array) $this->getBrowserVersions() as $operator => $version) {
            $denied |= (bool) version_compare($client_version, $version, $operator);
        }

        return (bool) $denied;
    }

    /**
     * Checks to see if all browsers of the device family is blocked.
     *
     * @return bool
     */
    public function isMatchedDevice()
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
    public function needsRedirecting()
    {
        return !$this->block_filter xor $this->isMatched();
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
    public function onRedirectPath(Request $request)
    {
        return $request->session()
                       ->get('redirected', false);
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
     * Validate a device browser stanza in the rules.
     *
     * @param string $device Device name
     * @param string $browser Browser name
     * @param array|string $versions Array of browser versions or '*' for all versions
     *
     * @return void
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validateBrowserRules($device, $browser, $versions)
    {
        if (!is_string($browser)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    "Device [%s] browsers must be a string form of the name.",
                    $device
                )
            );
        }

        if ('*' === $versions) {
            return;
        }

        if (!is_array($versions)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    "The value for [%s] must be either an array of browsers or an asterisk (*) for all browsers.",
                    $browser
                )
            );
        }

        foreach ($versions as $operator => $version) {
            $this->validateBrowserVersionRules($device, $browser, $operator, $version);
        }
    }

    /**
     * Validate a browser version stanza in the rules.
     *
     * @param string $device Device name
     * @param string $browser Browser name
     * @param string $operator Comparison operator
     * @param string $version Version of browser
     *
     * @return void
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validateBrowserVersionRules($device, $browser, $operator, $version)
    {
        if (!is_string($version)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    "Device [%s] browser [%s] version [%s] must be a string form of the version.",
                    $device,
                    $browser,
                    $version
                )
            );
        }

        if (!in_array(
            $operator,
            ['<', 'lt', '<=', 'le', '>', 'gt', '>=', 'ge', '==', '=', 'eq', '!=', '<>', 'ne'],
            true
        )) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    "The comparison operator [%s] for [%s > %s] is invalid.",
                    $operator,
                    $device,
                    $browser
                )
            );
        }
    }

    /**
     * Validate a device stanza in the rules.
     *
     * @param string $device Device name
     * @param array|string $browsers Array of device browsers or '*' for all versions
     *
     * @return void
     *
     * @throws InvalidRuleDefinitionsException
     */
    protected function validDeviceRule($device, $browsers)
    {
        if (!is_string($device)) {
            throw new InvalidRuleDefinitionsException('Devices must be a string form of the name.');
        }

        if ('*' === $browsers) {
            return;
        }

        if (!is_array($browsers)) {
            throw new InvalidRuleDefinitionsException(
                sprintf(
                    "The value for [%s] must be either an array of browsers or an asterisk (*) for all browsers.",
                    $device
                )
            );
        }

        foreach ($browsers as $browser => $versions) {
            $this->validateBrowserRules($device, $browser, $versions);
        }
    }

    /**
     * Validate the rules.
     *
     * @return void
     *
     * @throws InvalidRuleDefinitionsException
     */
    public function validateRules()
    {
        if (empty($this->getRules())) {
            return;
        }

        foreach ($this->getRules() as $device => $browsers) {
            $this->validDeviceRule($device, $browsers);
        }
    }
}
