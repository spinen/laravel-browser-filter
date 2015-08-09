<?php

namespace Tests\Spinen\BrowserFilter;

use Spinen\BrowserFilter\ParserCreator;
use UAParser\Result\Client;

class ParserCreatorTest extends TestCase
{
    /**
     * @var ParserCreator
     */
    protected $parser;

    protected function setUp()
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
}
