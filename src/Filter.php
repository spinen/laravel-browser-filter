<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Mobile_Detect;
use UAParser\Parser;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter
 */
class Filter
{
    /**
     * @var \UAParser\Result\Client
     */
    private $client;

    /**
     * The config repository instance.
     *
     * @var Configs
     */
    protected $config;

    /**
     * @var string
     */
    protected $config_path = 'browserfilter.blocked.';

    /**
     * The mobile detector instance.
     *
     * @var Mobile_Detect
     */
    private $detector;

    /**
     * Create a new browser filter middleware instance.
     *
     * @param Config        $config   Config
     * @param Mobile_Detect $detector Mobile_Detect
     */
    public function __construct(Config $config, Mobile_Detect $detector)
    {
        $this->config = $config;
        $this->detector = $detector;
        $this->client = Parser::create()
                              ->parse($this->detector->getUserAgent());
    }

    /**
     * Get the browsers being filtered.
     *
     * @return string|array
     */
    private function getBlockedBrowsers()
    {
        return $this->config->get($this->config_path . $this->client->device->family);
    }

    /**
     * Get the versions of the browsers being filtered.
     *
     * @return string|array
     */
    private function getBlockedBrowserVersions()
    {
        return $this->config->get($this->config_path . $this->client->device->family . '.' . $this->client->ua->family);
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
        // TODO: Wrap this with the cache repository
        if ($this->isBlocked()) {
            dd("Denied");
            // TODO: Return proper view here that is configurable
        }

        return $next($request);
    }

    /**
     * Checks to see if the browser/client is blocked.
     *
     * @return bool
     */
    private function isBlocked()
    {
        return ($this->isBlockedDevice() || $this->isBlockedBrowser() || $this->isBlockedBrowserVersion());
    }

    /**
     * Checks to see if all versions of the browser is blocked.
     *
     * @return bool
     */
    private function isBlockedBrowser()
    {
        return $this->getBlockedBrowserVersions() === '*';
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
    private function isBlockedBrowserVersion()
    {
        $denied = false;

        // cache it, so that we don't have to keep asking for it
        $client_version = $this->client->ua->toVersion();

        foreach ((array)$this->getBlockedBrowserVersions() as $operator => $version) {
            $denied |= (bool)version_compare($client_version, $version, $operator);
        }

        return $denied;
    }

    /**
     * Checks to see if all browsers of the device family is blocked.
     *
     * @return bool
     */
    private function isBlockedDevice()
    {
        return $this->getBlockedBrowsers() === '*';
    }
}
