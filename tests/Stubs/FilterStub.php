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
    public function parseFilterString($filter_string)
    {
        $this->rules = explode(',', $filter_string);
    }

    /**
     * @inheritDoc
     */
    public function process(Request $request, Closure $next)
    {
        return "Stub";
    }

    /**
     * Method in the stub to allow you to set the block_filter for testing.
     *
     * @param bool $block_filter Set type
     *
     * @return void
     */
    public function setBlockFilter($block_filter)
    {
        $this->block_filter = $block_filter;
    }

    /**
     * Method in the stub to allow you to set the redirect_route for testing.
     *
     * @param string $redirect_route Set the name of the route to redirect
     *
     * @return void
     */
    public function setRedirectRoute($redirect_route)
    {
        $this->redirect_route = $redirect_route;
    }

    /**
     * Method in the stub to allow you to set the rules for testing.
     *
     * @param array $rules Array of rules
     *
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
}
