<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

class Filter
{
    /**
     * The config repository instance.
     *
     * @var Config
     */
    protected $config;

    /**
     * Create a new browser filter middleware instance.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Do whatever work needed

        return $next($request);
    }
}
