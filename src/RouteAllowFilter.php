<?php

namespace Spinen\BrowserFilter;

use Closure;
use Illuminate\Http\Request;

class RouteAllowFilter extends RouteFilter
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
