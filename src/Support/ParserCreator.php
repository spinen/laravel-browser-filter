<?php

namespace Spinen\BrowserFilterSupport;

use UAParser\Parser;

/**
 * Class ParserCreator
 *
 * @package Spinen\BrowserFilter
 */
class ParserCreator
{
    /**
     * Create a new parser instance.
     */
    public function __construct()
    {
        // NOTE: Has to be called statically, so cannot inject it.
        $this->parser = Parser::create();
    }

    /**
     * Parse the user agent string.
     *
     * @param string $agent Agent string
     *
     * @return \UAParser\Result\Client
     */
    public function parseAgent($agent)
    {
        return $this->parser->parse($agent);
    }
}
