<?php

namespace Spinen\BrowserFilter\Stack;

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
 * @package Spinen\BrowserFilter\Stack
 */
class Filter
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
     * The redirector instance.
     *
     * @var Redirector
     */
    protected $redirector;

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
     * Generate the key to use to cache the determination.
     *
     * @return string
     */
    protected function generateCacheKey()
    {
        return $this->client->device->family . ':' . $this->client->ua->family . ':' . $this->client->ua->toVersion();
    }

    /**
     * @inheritDoc
     */
    public function getBlockedBrowsers()
    {
        return $this->config->get($this->config_path . 'blocked.' . $this->client->device->family);
    }

    /**
     * @inheritDoc
     */
    public function getBlockedBrowserVersions()
    {
        return $this->config->get($this->config_path .
                                  'blocked.' .
                                  $this->client->device->family .
                                  '.' .
                                  $this->client->ua->family);
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
     * @inheritDoc
     */
    public function getRedirectRoute()
    {
        // TODO: This is a duplicate
        return $this->config->get($this->config_path . 'route');
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request Request
     * @param Closure $next    Closure
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->onRedirectPath($request)) {
            return $next($request);
        }

        $cache_key = $this->generateCacheKey();

        $redirect = $this->cache->get($cache_key);

        if (is_null($redirect)) {
            $redirect = $this->determineRedirect($cache_key);

            $this->cache->put($cache_key, $redirect, $this->getCacheTimeout());
        }

        if ($redirect) {
            return $redirect;
        }

        return $next($request);
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
        return $request->path() === $this->getRedirectRoute();
    }
}
