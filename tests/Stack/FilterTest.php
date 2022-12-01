<?php

namespace Spinen\BrowserFilter\Stack;

use Spinen\BrowserFilter\Exceptions\InvalidFilterTypeException;
use Spinen\BrowserFilter\FilterCase;

/**
 * Class FilterTest
 */
class FilterTest extends FilterCase
{
    /**
     * {@inheritdoc}
     */
    protected function createFilter()
    {
        $this->filter = new Filter(
            $this->cache_mock, $this->config_mock, $this->detector_mock, $this->parser_mock, $this->redirector_mock
        );
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
    public function it_generates_the_cache_key()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->withArgs(['browserfilter.rules', []])
                          ->andReturn([]);

        $this->assertEquals(
            md5(json_encode([])).':Device:Browser:1.2.3',
            $this->filter->generateCacheKey($this->request_mock)
        );
    }

    /**
     * @test
     */
    public function it_reads_the_allow_filter_type_from_the_configs()
    {
        $this->config_mock->shouldReceive('get')
                          ->withArgs(['browserfilter.rules', []])
                          ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.type')
                          ->andReturn('allow');

        $this->filter->parseFilterString(null);

        $this->assertEquals('allow', $this->filter->getFilterType());
    }

    /**
     * @test
     */
    public function it_reads_the_block_filter_type_from_the_configs()
    {
        $this->config_mock->shouldReceive('get')
                          ->withArgs(['browserfilter.rules', []])
                          ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.type')
                          ->andReturn('block');

        $this->filter->parseFilterString(null);

        $this->assertEquals('block', $this->filter->getFilterType());
    }

    /**
     * @test
     */
    public function it_raises_exception_to_invalid_filter_type_in_configs()
    {
        $this->expectException(InvalidFilterTypeException::class);

        $this->config_mock->shouldReceive('get')
                          ->withArgs(['browserfilter.rules', []])
                          ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.type')
                          ->andReturn('invalid');

        $this->filter->parseFilterString(null);

        $this->filter->getFilterType();
    }

    /**
     * @test
     */
    public function it_reads_the_rules_from_the_configs()
    {
        $rules = [
            'Device' => [
                'Browser' => '*',
            ],
        ];

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->withArgs(['browserfilter.rules', []])
                          ->andReturn($rules);

        $this->config_mock->shouldReceive('get')
                          ->with('browserfilter.type')
                          ->andReturn('allow');

        $this->filter->parseFilterString(null);

        $this->assertEquals($rules, $this->filter->getRules());
    }
}
