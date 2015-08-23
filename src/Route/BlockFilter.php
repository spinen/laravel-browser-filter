<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Contracts\Filterable;

/**
 * Class BlockFilter
 *
 * @package Spinen\BrowserFilter\Route
 */
class BlockFilter extends Filter implements Filterable
{
    /**
     * @inheritDoc
     */
    function process(Request $request, Closure $next, array $filter)
    {
        dd($filter);
        //TODO: Write the logic here
    }
}
