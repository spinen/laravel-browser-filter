<?php

namespace Tests\Spinen\BrowserFilter\Route\Stubs;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Route\Filter;

/**
 * Class FilterStub
 *
 * @package Tests\Spinen\BrowserFilter\Route\Stubs
 */
class FilterStub extends Filter
{
    /**
     * @inheritDoc
     */
    function process(Request $request, Closure $next, array $filter)
    {
        // TODO: Implement process() method.
    }
}
