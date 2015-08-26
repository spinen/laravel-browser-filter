<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Filter;

/**
 * Class BlockFilter
 *
 * @package Spinen\BrowserFilter\Route
 */
class BlockFilter extends Filter
{
    use StringFilterParser;

    /**
     * @inheritDoc
     */
    protected $blockFilter = true;

    /**
     * @inheritDoc
     */
    protected function process(Request $request, Closure $next)
    {
        $redirect = $this->determineRedirect();

        if ($redirect) {
            return $redirect;
        }

        return $next($request);
    }
}
