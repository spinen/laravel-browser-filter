<?php

namespace Spinen\BrowserFilter\Stack;

use Spinen\BrowserFilter\Filter as CoreFilter;

/**
 * Class Filter
 *
 * @package Spinen\BrowserFilter\Stack
 */
class Filter extends CoreFilter
{
    /**
     * @inheritDoc
     */
    protected $block_filter = true;

    /**
     * @inheritDoc
     */
    public function parseFilterString($filter_string)
    {
        // TODO: Check for allowed or blocked
        $this->rules = $this->config->get($this->config_path . 'blocked', []);
    }
}
