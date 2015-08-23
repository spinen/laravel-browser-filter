<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Spinen\BrowserFilter\Contracts\Filterable;
use Spinen\BrowserFilter\Support\DecipherRules;
use Spinen\BrowserFilter\Support\ParserCreator;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter\Route
 */
abstract class Filter implements Filterable
{
    use DecipherRules;

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
     * @param Config        $config     Config
     * @param Mobile_Detect $detector   Mobile_Detect
     * @param ParserCreator $parser     ParserCreator
     * @param Redirector    $redirector Redirector
     */
    public function __construct(
        Config $config,
        Mobile_Detect $detector,
        ParserCreator $parser,
        Redirector $redirector
    ) {
        $this->config = $config;
        $this->detector = $detector;
        $this->client = $parser->parseAgent($this->detector->getUserAgent());
        $this->redirector = $redirector;
    }

    /**
     * Loop through all of the parameters in the string and process them.
     *
     * @param string $filter The filter separated by '/'
     *
     * @return void
     */
    private function extractRule($filter)
    {
        list($device, $browser, $operator_versions) = array_pad(array_filter(explode('/', $filter, 3)), 3, '*');

        // Block all browsers of the device
        if ('*' === $browser) {
            $this->rules[$device] = '*';

            return;
        }

        // Block all versions of the browser
        if ('*' === $operator_versions) {
            $this->rules[$device][$browser] = '*';

            return;
        }

        $this->rules[$device][$browser] = $this->extractVersions($device, $browser, $operator_versions);

        return;
    }

    /**
     * Loop through all of the versions in the string and process them.
     *
     * @param string $device            The device
     * @param string $browser           The browser
     * @param string $operator_versions The versions separated by '|'
     *
     * @return array
     */
    private function extractVersions($device, $browser, $operator_versions)
    {
        // Were there existing rules for the browser?
        $versions = empty($this->rules[$device][$browser]) ? [] : $this->rules[$device][$browser];

        foreach (array_filter(explode('|', $operator_versions)) as $operator_version) {
            // Remove everything to the leading numbers
            $version = preg_replace("/^[^\\d]*/u", "", $operator_version);
            // Default no operator to equals
            $operator = str_replace($version, '', $operator_version) ?: '=';

            $versions[$operator] = $version;
        }

        return $versions;
    }

    /**
     * @inheritDoc
     */
    public function getBlockedBrowsers()
    {
        return ($this->haveRulesForDevice()) ? $this->rules[$this->client->device->family] : null;
    }

    /**
     * @inheritDoc
     */
    public function getBlockedBrowserVersions()
    {
        return ($this->haveVersionsForBrowser())
            ? $this->rules[$this->client->device->family][$this->client->ua->family] : null;
    }

    /**
     * @inheritDoc
     */
    public function getRedirectRoute()
    {
        // TODO: This is a duplicate
        return ($this->redirect_route) ?: $this->config->get($this->config_path . 'route');
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
     * @param Request $request        Request
     * @param Closure $next           Closure
     * @param string  $filter_string  string
     * @param string  $redirect_route string
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $filter_string, $redirect_route = null)
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
    private function haveRulesForDevice()
    {
        return array_key_exists($this->client->device->family, $this->rules);
    }

    /**
     * Check to see if there are defined versions for the browser for the device.
     *
     * @return bool
     */
    private function haveVersionsForBrowser()
    {
        return array_key_exists($this->client->device->family, $this->rules) &&
               array_key_exists($this->client->ua->family, $this->rules[$this->client->device->family]);
    }

    /**
     * Loop through all of the filters in the string and process them.
     *
     * @param string $filter_string The filters separated by ';'
     */
    public function parseFilterString($filter_string)
    {
        array_map([$this, 'extractRule'], array_filter(explode(';', $filter_string)));
    }

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
