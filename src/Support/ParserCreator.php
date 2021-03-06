<?php

namespace Spinen\BrowserFilter\Support;

use UAParser\Parser;

/**
 * Class ParserCreator
 *
 * @package Spinen\BrowserFilter\Support
 */
class ParserCreator
{
    /**
     * User Agent Parser
     *
     * @var Parser
     */
    protected $parser;

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
        return $this->parser->parse((string) $agent);
    }
}
