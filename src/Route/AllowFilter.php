<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Filter;

/**
 * Class AllowFilter
 *
 * @package Spinen\BrowserFilter\Route
 */
class AllowFilter extends Filter
{
    use StringFilterParser;

    /**
     * @inheritDoc
     */
    protected $blockFilter = false;

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
