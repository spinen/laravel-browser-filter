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
    protected function process(Request $request, Closure $next)
    {
        // TODO: This is just slimed in to make it work
        if ($this->isBlocked()) {
            return $this->redirector->route($this->getRedirectRoute());
        }

        return $next($request);
    }
}
