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
    /**
     * @inheritDoc
     */
    function process(Request $request, Closure $next, array $filter)
    {
        dd($filter);
        //TODO: Write the logic here
    }
}
