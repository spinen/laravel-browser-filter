<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter
 */
class Filter
{
    /**
     * The config repository instance.
     *
     * @var Configs
     */
    protected $config;

    /**
     * Create a new browser filter middleware instance.
     *
     * @param Config $config Config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
        // Do whatever work needed

        return $next($request);
    }
}
