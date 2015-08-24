<?php

namespace Tests\Spinen\BrowserFilter\Route\Stubs;

use Spinen\BrowserFilter\Route\StringFilterParser;

/**
 * Class FilterStub
 *
 * @package Tests\Spinen\BrowserFilter\Route\Stubs
 */
class FilterStub
{
    use StringFilterParser;

    /**
     * @var array
     */
    protected $rules = [];

    /**
     * @return array
     */
    public function getRules()
    {
        return $this->rules;
    }
}
