<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Contracts\Filterable;

/**
 * Class AllowFilter
 *
 * @package Spinen\BrowserFilter\Route
 */
class AllowFilter extends Filter implements Filterable
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
