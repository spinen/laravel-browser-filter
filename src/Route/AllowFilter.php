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
    protected function process(Request $request, Closure $next)
    {
        // TODO: This is just slimed in to make it work
        if ($this->isBlocked()) {
            return $next($request);
        }

        return $this->redirector->route($this->getRedirectRoute());
    }
}
