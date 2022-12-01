<?php

namespace Spinen\BrowserFilter\Support;

use UAParser\Parser;
use UAParser\Result\Client;

/**
 * Class ParserCreator
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
     */
    public function parseAgent(string $agent): Client
    {
        return $this->parser->parse((string) $agent);
    }
}
