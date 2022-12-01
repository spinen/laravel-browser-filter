<?php

namespace Spinen\BrowserFilter\Support;

use Spinen\BrowserFilter\TestCase;
use UAParser\Result\Client;

/**
 * Class ParserCreatorTest
 */
class ParserCreatorTest extends TestCase
{
    /**
     * @var ParserCreator
     */
    protected $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new ParserCreator();
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(ParserCreator::class, $this->parser);
    }

    /**
     * @test
     */
    public function it_returns_a_client_after_parsing_agent()
    {
        $this->assertInstanceOf(Client::class, $this->parser->parseAgent('SomeAgent'));
    }

    /**
     * @test
     */
    public function it_will_parse_a_null_agent_string()
    {
        $this->assertInstanceOf(Client::class, $this->parser->parseAgent(''));
    }
}
