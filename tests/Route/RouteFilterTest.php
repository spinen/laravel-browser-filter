<?php

namespace Tests\Spinen\BrowserFilter\Route;

use Tests\Spinen\BrowserFilter\Route\Stubs\FilterStub as Filter;
use Tests\Spinen\BrowserFilter\TestCase;

class StringFilterParserTest extends TestCase
{
    /**
     * @var Filter
     */
    protected $filter;

    protected function setUp()
    {
        $this->filter = new Filter();

        parent::setUp();
    }

    /**
     * @test
     */
    public function it_parses_a_filter_string_where_it_understands_to_default_remaining_parameters()
    {
        $filter_string = 'First;Second/Third;Fourth/Fifth/6';
        $expected = [
            'First'  => '*',
            'Second' => [
                'Third' => '*',
            ],
            'Fourth' => [
                'Fifth' => [
                    '=' => '6',
                ],
            ],
        ];

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($expected, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_a_filter_string_and_ignores_null_values()
    {
        $filter_string = 'First/;Second/Third/;Fourth/Fifth/6|;';
        $expected = [
            'First'  => '*',
            'Second' => [
                'Third' => '*',
            ],
            'Fourth' => [
                'Fifth' => [
                    '=' => '6',
                ],
            ],
        ];

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($expected, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_merges_values_when_parsing_a_filter_string()
    {
        $filter_string = 'First/Second/<=3;First/Second/>4;First/Fifth';
        $expected = [
            'First' => [
                'Second' => [
                    '<=' => '3',
                    '>'  => '4',
                ],
                'Fifth'  => '*',
            ],
        ];

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($expected, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_allows_a_star_or_default_to_override_existing()
    {
        $filter_string = 'First/Second/<=2;First/Second;Third/Forth/=3;Third';
        $expected = [
            'First' => [
                'Second' => '*',
            ],
            'Third' => '*',
        ];

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($expected, $this->filter->getRules());
    }
}
