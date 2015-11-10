<?php

namespace Spinen\BrowserFilter\Stubs;

use Spinen\BrowserFilter\Filter;

/**
 * Class FilterStub
 *
 * @package Spinen\BrowserFilter\Route\Stubs
 */
class FilterStub extends Filter
{
    use TestSetters;

    /**
     * @inheritDoc
     */
    public function parseFilterString($filter_string)
    {
        if (is_array($filter_string)) {
            $this->rules = $filter_string;

            return;
        }
        $this->rules = array_filter(explode(',', $filter_string));
    }
}
