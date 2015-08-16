<?php

namespace Spinen\BrowserFilter\Route;

use Closure;
use Illuminate\Http\Request;

class BlockFilter extends Filter
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
