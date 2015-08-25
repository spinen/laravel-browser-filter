<?php

namespace Spinen\BrowserFilter\Stack;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Filter as CoreFilter;
use Spinen\BrowserFilter\Support\ParserCreator;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter\Stack
 */
class Filter extends CoreFilter
{
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
    public function parseFilterString($filter_string)
    {
        $this->rules = $this->config->get($this->config_path . 'blocked', []);
    }

    /**
     * @inheritDoc
     */
    protected function process(Request $request, Closure $next)
    {
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
}
