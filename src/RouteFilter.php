<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;

abstract class RouteFilter
{
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
     * Handle an incoming request.
     *
     * @param Request $request  Request
     * @param Closure $next     Closure
     * @param string  $device   string
     * @param string  $browser  string
     * @param string  $operator string
     * @param string  $version  string
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $device, $browser = '*', $operator = '=', $version = '*')
    {
        // All browsers for a specific device
        if ($browser === '*') {
            return $this->process($request, $next, [$device => '*']);
        }

        // All versions for a specific browser
        if ($version === '*') {
            return $this->process($request, $next, [$device => [$browser => '*']]);
        }

        return $this->process($request, $next, [$device => [$browser => [$operator => $version]]]);
    }

    /**
     * Delegate the processing of the filter to classes that know the logic that they need to preform.
     *
     * @param Request $request Request
     * @param Closure $next    Closure
     * @param array   $filter  array
     *
     * @return mixed
     */
    abstract function process(Request $request, Closure $next, array $filter);
}
