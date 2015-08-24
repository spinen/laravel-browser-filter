<?php

namespace Tests\Spinen\BrowserFilter\Stubs;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Filter;

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
