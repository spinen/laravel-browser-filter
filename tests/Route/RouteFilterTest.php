<?php

namespace Spinen\BrowserFilter\Route;

use Spinen\BrowserFilter\FilterCase;
use Spinen\BrowserFilter\Stubs\RouteFilterStub as Filter;

/**
 * Class RouteFilterTest
 */
class RouteFilterTest extends FilterCase
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
        $this->filter->setFilterAsAllowFilter();

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';
        $path = 'route';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn($path);

        $this->assertEquals(
            'Device:Browser:1.2.3:allow:'.md5($path),
            $this->filter->generateCacheKey($this->request_mock)
        );
    }

    /**
     * @test
     */
    public function it_does_not_try_to_parse_an_empty_filter_string()
    {
        $this->filter->parseFilterString(null);

        $this->assertEquals([], $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_a_filter_string_where_it_understands_to_default_remaining_parameters()
    {
        $filter_string = 'First;Second/Third;Fourth/Fifth/6';
        $rules = [
            'First' => '*',
            'Second' => [
                'Third' => '*',
            ],
            'Fourth' => [
                'Fifth' => [
                    '=' => '6',
                ],
            ],
        ];

        $cache_key = 'filter_string:'.md5($filter_string);

        $timeout = random_int(1, 100);

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->withArgs([$cache_key, []])
                         ->andReturn([]);

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 $cache_key,
                                 $rules,
                                 $timeout,
                             ]
                         )
                         ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_a_filter_string_and_ignores_null_values()
    {
        $filter_string = 'First/;Second/Third/;Fourth/Fifth/6|;';
        $rules = [
            'First' => '*',
            'Second' => [
                'Third' => '*',
            ],
            'Fourth' => [
                'Fifth' => [
                    '=' => '6',
                ],
            ],
        ];

        $cache_key = 'filter_string:'.md5($filter_string);

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->withArgs([$cache_key, []])
                         ->andReturn([]);

        $timeout = random_int(1, 100);

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 $cache_key,
                                 $rules,
                                 $timeout,
                             ]
                         )
                         ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_merges_values_when_parsing_a_filter_string()
    {
        $filter_string = 'First/Second/<=3;First/Second/>4;First/Fifth';
        $rules = [
            'First' => [
                'Second' => [
                    '<=' => '3',
                    '>' => '4',
                ],
                'Fifth' => '*',
            ],
        ];

        $cache_key = 'filter_string:'.md5($filter_string);

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->withArgs([$cache_key, []])
                         ->andReturn([]);

        $timeout = random_int(1, 100);

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 $cache_key,
                                 $rules,
                                 $timeout,
                             ]
                         )
                         ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_allows_a_star_or_default_to_override_existing()
    {
        $filter_string = 'First/Second/<=2;First/Second;Third/Forth/=3;Third';
        $rules = [
            'First' => [
                'Second' => '*',
            ],
            'Third' => '*',
        ];

        $cache_key = 'filter_string:'.md5($filter_string);

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->withArgs([$cache_key, []])
                         ->andReturn([]);

        $timeout = random_int(1, 100);

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 $cache_key,
                                 $rules,
                                 $timeout,
                             ]
                         )
                         ->andReturn([]);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_will_not_reparse_the_filter_string_if_it_is_already_cached()
    {
        $filter_string = 'First/Second/<=2;First/Second;Third/Forth/=3;Third';
        $rules = [
            'Cached' => [
                'Rules' => '*',
            ],
        ];

        $cache_key = 'filter_string:'.md5($filter_string);

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->withArgs([$cache_key, []])
                         ->andReturn($rules);

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withArgs(
                             [
                                 $cache_key,
                                 $rules,
                                 random_int(1, 100),
                             ]
                         );

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->filter->parseFilterString($filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }
}
