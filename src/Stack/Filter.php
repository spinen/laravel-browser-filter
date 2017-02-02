<?php

namespace Spinen\BrowserFilter\Stack;

use Spinen\BrowserFilter\Exceptions\InvalidFilterTypeException;
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
        // NOTE: $filter_string is unused, but needed to match signature of the method.

        $this->setFilterType($this->config->get($this->config_path . 'type'));

        $this->rules = $this->config->get($this->config_path . 'rules', []);
    }

    /**
     * Set the filter type.
     *
     * @param $type
     *
     * @return void
     *
     * @throws InvalidFilterTypeException
     */
    protected function setFilterType($type)
    {
        if ('allow' === $type) {
            $this->block_filter = false;

            return;
        }

        if ('block' === $type) {
            $this->block_filter = true;

            return;
        }

        throw new InvalidFilterTypeException(sprintf(
            "Invalid filter type [%s] was given. Only allow or block are permitted",
            $type
        ));
    }
}
