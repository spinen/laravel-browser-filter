<?php

namespace Spinen\BrowserFilter\Stubs;

use Spinen\BrowserFilter\Filter;

/**
 * Class FilterStub
 */
class FilterStub extends Filter
{
    use TestSetters;

    /**
     * {@inheritDoc}
     */
    public function parseFilterString(string|array|null $filter_string): void
    {
        if (is_array($filter_string)) {
            $this->rules = $filter_string;

            return;
        }
        $this->rules = array_filter(explode(',', (string) $filter_string));
    }
}
