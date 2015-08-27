<?php

namespace Tests\Spinen\BrowserFilter\Stubs;

use Spinen\BrowserFilter\Filter;

/**
 * Class FilterStub
 *
 * @package Tests\Spinen\BrowserFilter\Route\Stubs
 */
class FilterStub extends Filter
{
    use TestSetters;

    /**
     * @inheritDoc
     */
    public function parseFilterString($filter_string)
    {
        $this->rules = explode(',', $filter_string);
    }
}
