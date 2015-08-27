<?php

namespace Tests\Spinen\BrowserFilter\Stack;

use Mockery;
use Spinen\BrowserFilter\Stack\Filter;
use Tests\Spinen\BrowserFilter\FilterCase;

/**
 * Class FilterTest
 *
 * @package Tests\Spinen\BrowserFilter\Stack
 */
class FilterTest extends FilterCase
{
    /**
     * @inheritdoc
     */
    protected function createFilter()
    {
        $this->filter = new Filter($this->cache_mock, $this->config_mock, $this->detector_mock, $this->parser_mock,
            $this->redirector_mock);
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(Filter::class, $this->filter);
    }

    /**
     * @test
     */
    public function it_sets_the_filter_type_to_block()
    {
        $this->assertEquals('block', $this->filter->getFilterType());
    }

    /**
     * @test
     */
    public function it_parses_the_rules()
    {
        $rules = [
            'Device' => [
                'Browser' => '*',
            ],
        ];

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs(['browserfilter.blocked', []])
                          ->andReturn($rules);

        $this->filter->parseFilterString(null);

        $this->assertEquals($rules, $this->filter->getRules());
    }
}
