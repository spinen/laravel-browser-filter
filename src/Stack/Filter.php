<?php

namespace Spinen\BrowserFilter\Stack;

use Illuminate\Http\Request;
use Spinen\BrowserFilter\Exceptions\InvalidFilterTypeException;
use Spinen\BrowserFilter\Filter as CoreFilter;

/**
 * Class Filter
 */
class Filter extends CoreFilter
{
    /**
     * {@inheritDoc}
     */
    protected ?bool $block_filter = true;

    /**
     * {@inheritDoc}
     */
    public function generateCacheKey(Request $request): string
    {
        // Append the rules with the version of the browser to allow new rules to bust the cache
        return md5(json_encode($this->config->get($this->config_path.'rules', []))).
            ':'.
            parent::generateCacheKey($request);
    }

    /**
     * {@inheritDoc}
     */
    public function parseFilterString($filter_string): void
    {
        // NOTE: $filter_string is unused, but needed to match signature of the method.

        $this->setFilterType($this->config->get($this->config_path.'type'));

        $this->rules = $this->config->get($this->config_path.'rules', []);
    }

    /**
     * Set the filter type.
     *
     * @throws InvalidFilterTypeException
     */
    protected function setFilterType(string $type): void
    {
        if ('allow' === $type) {
            $this->block_filter = false;

            return;
        }

        if ('block' === $type) {
            $this->block_filter = true;

            return;
        }

        throw new InvalidFilterTypeException(
            sprintf(
                'Invalid filter type [%s] was given. Only allow or block are permitted.',
                $type
            )
        );
    }
}
