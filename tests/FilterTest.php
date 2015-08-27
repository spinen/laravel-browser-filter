<?php

namespace Tests\Spinen\BrowserFilter;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use Tests\Spinen\BrowserFilter\Stubs\FilterStub as Filter;

/**
 * Class FilterTest
 *
 * @package Tests\Spinen\BrowserFilter\Route
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
    public function it_returns_the_route_name_when_determining_route_for_client_that_needs_redirecting()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => '*',
        ];

        $this->filter->setBlockFilter(false);

        $this->filter->setRules($rules);

        $this->assertEquals('route', $this->filter->determineRedirect());
    }

    /**
     * @test
     */
    public function it_returns_false_when_determining_route_for_client_that_does_not_need_redirecting()
    {
        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->filter->setBlockFilter(true);

        $this->assertEquals(false, $this->filter->determineRedirect());
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

        $this->assertEquals('Device:Browser:1.2.3', $this->filter->generateCacheKey($this->request_mock));
    }

    /**
     * @test
     */
    public function it_gets_the_correct_rules_for_the_device()
    {
        $rules = [
            'Device' => [
                'Browser' => [
                    '<=' => '3',
                    '>'  => '4',
                ],
                'Other'   => '*',
            ],
        ];

        $this->filter->setRules($rules);

        $device = 'Device';
        $this->client_device_mock->family = $device;

        $this->assertEquals($rules[$device], $this->filter->getBrowsers());
    }

    /**
     * @test
     */
    public function it_returns_null_when_there_are_no_rules_for_the_device()
    {
        $this->assertEquals(null, $this->filter->getBrowsers());
    }

    /**
     * @test
     */
    public function it_gets_the_correct_rules_for_the_browser()
    {
        $rules = [
            'Device' => [
                'Browser' => [
                    '<=' => '3',
                    '>'  => '4',
                ],
                'Other'   => '*',
            ],
        ];

        $this->filter->setRules($rules);

        $device = 'Device';
        $ua = 'Browser';

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->assertEquals($rules[$device][$ua], $this->filter->getBrowserVersions());
    }

    /**
     * @test
     */
    public function it_returns_null_when_there_are_no_versions_for_the_browser()
    {
        $this->assertEquals(null, $this->filter->getBrowserVersions());
    }

    /**
     * @test
     */
    public function it_gets_the_cache_timeout_from_the_proper_key_in_the_config()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn('x');

        $this->assertEquals('x', $this->filter->getCacheTimeout());
    }

    /**
     * @test
     */
    public function it_uses_the_configuration_redirect_route_as_the_default()
    {
        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->assertEquals('route', $this->filter->getRedirectRoute());
    }

    /**
     * @test
     */
    public function it_uses_the_redirect_route_over_the_config_value_if_it_is_set()
    {
        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.route');

        $this->filter->setRedirectRoute('set_route');

        $this->assertEquals('set_route', $this->filter->getRedirectRoute());
    }

    /**
     * @test
     */
    public function it_returns_the_rules()
    {
        $rules = [
            'Some' => 'Rule',
        ];

        $this->filter->setRules($rules);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_on_the_redirect_path()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('route');

        $this->assertEquals($this->request_mock, $this->filter->handle($this->request_mock, $this->returnGiven()));
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_client_is_not_blocked_and_caches_the_result()
    {
        $this->filter->setBlockFilter(true);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             'Device:Browser:1.2.3',
                             false,
                             'x',
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn('x');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('other_route');

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $response);

        $this->assertEquals($this->request_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_the_results_are_cached_as_not_blocked()
    {
        $this->filter->setBlockFilter(true);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturn(false);

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withAnyArgs();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('other_route');

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $response);

        $this->assertEquals($this->request_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_from_the_filter_when_client_is_blocked_and_caches_the_result()
    {
        $this->filter->setBlockFilter(false);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             'Device:Browser:1.2.3',
                             'route',
                             'x',
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn('x');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('other_route');

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals($this->redirect_response_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_the_client_is_cached_as_not_blocked()
    {
        $this->filter->setBlockFilter(false);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturn('route');

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withAnyArgs();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('other_route');

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals($this->redirect_response_mock, $response);
    }

    /**
     * @test
     */
    public function it_parses_the_third_parameter_and_causes_the_rules_to_be_set()
    {
        $this->filter->setBlockFilter(true);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->withAnyArgs()
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->with('browserfilter.timeout')
                          ->andReturn('x');

        $this->request_mock->shouldReceive('path')
                           ->withNoArgs()
                           ->andReturn('other_route');

        $filter_string = 'Device,Other';

        $rules = [
            'Device',
            'Other',
        ];

        $this->filter->handle($this->request_mock, $this->returnGiven(), $filter_string);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_the_fourth_parameter_and_causes_the_redirect_route_to_be_set()
    {
        $this->filter->setBlockFilter(false);

        $redirect = 'some_route';

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->withAnyArgs()
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.route');

        $this->config_mock->shouldReceive('get')
                          ->with('browserfilter.timeout')
                          ->andReturn('x');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with($redirect)
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->withNoArgs()
                           ->andReturn('other_route');

        $this->filter->handle($this->request_mock, $this->returnGiven(), null, $redirect);

        $this->assertEquals($redirect, $this->filter->getRedirectRoute());
    }

    /**
     * @test
     */
    public function it_knows_if_there_are_rules_for_a_device()
    {
        $rules = [
            'Device' => '*',
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';

        $this->assertEquals(true, $this->filter->haveRulesForDevice());

        $this->client_device_mock->family = 'Browser';

        $this->assertEquals(false, $this->filter->haveRulesForDevice());
    }

    /**
     * @test
     */
    public function it_knows_if_there_are_versions_for_a_browser_for_a_device()
    {
        $rules = [
            'Device' => [
                'Browser' => '*',
            ],
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';

        $this->assertEquals(true, $this->filter->haveRulesForDevice());

        $this->client_device_mock->family = 'Other';

        $this->assertEquals(false, $this->filter->haveRulesForDevice());
    }

    /**
     * @test
     */
    public function it_matches_when_all_browsers_for_a_device_is_defined()
    {
        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => '*',
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->assertEquals(true, $this->filter->isMatched());

        $this->client_device_mock->family = 'Other';

        $this->assertEquals(false, $this->filter->isMatched());
    }

    /**
     * @test
     */
    public function it_matches_when_all_versions_for_a_browser_is_defined()
    {
        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => '*',
            ],
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->assertEquals(true, $this->filter->isMatched());

        $this->client_device_mock->family = 'Other';

        $this->assertEquals(false, $this->filter->isMatched());
    }

    /**
     * @test
     */
    public function it_matches_when_a_version_for_a_browser_is_defined_in_the_rules()
    {
        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->assertEquals(true, $this->filter->isMatched());
    }

    /**
     * @test
     */
    public function it_treats_an_asterisk_as_all_versions_for_a_browser_as_a_match()
    {
        $rules = [
            'Device' => [
                'Browser' => '*',
            ],
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->assertEquals(true, $this->filter->isMatchedBrowser());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_equal_to_defined_equal_rule()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(6)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '=' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'eq' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '=' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'eq' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_greater_than_defined_greater_than_rule()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(4)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '>' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'gt' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '>' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'gt' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_equal_to_defined_greater_equal_rule()
    {

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(4)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '>=' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ge' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '>=' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ge' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_less_than_defined_less_than_rule()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(4)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '<' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'lt' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'lt' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_equal_to_defined_less_equal_rule()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(4)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '<=' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'le' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<=' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'le' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_matches_when_browser_version_is_not_equal_to_defined_not_equal_rule()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->times(6)
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $rules = [
            'Device' => [
                'Browser' => [
                    '!=' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<>' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ne' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '!=' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<>' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ne' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());
    }

    /**
     * @test
     */
    public function it_treats_an_asterisk_as_all_browsers_for_a_device_as_a_match()
    {
        $rules = [
            'Device' => '*',
        ];

        $this->filter->setRules($rules);

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->assertEquals(true, $this->filter->isMatchedDevice());
    }

    /**
     * @test
     */
    public function it_does_not_need_redirecting_if_browser_is_not_blocked()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->filter->setBlockFilter(true);

        $rules = [];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->needsRedirecting());
    }

    /**
     * @test
     */
    public function it_needs_redirecting_if_browser_is_blocked()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->filter->setBlockFilter(true);

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->needsRedirecting());
    }

    /**
     * @test
     */
    public function it_needs_redirecting_if_browser_is_not_allowed()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->filter->setBlockFilter(false);

        $rules = [];

        $this->filter->setRules($rules);

        $this->assertEquals(true, $this->filter->needsRedirecting());
    }

    /**
     * @test
     */
    public function it_does_not_need_redirecting_if_browser_is_allowed()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->filter->setBlockFilter(false);

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRules($rules);

        $this->assertEquals(false, $this->filter->needsRedirecting());
    }

    /**
     * @test
     */
    public function it_knows_if_the_request_is_to_a_redirect_path_so_that_the_filter_can_be_ignored()
    {
        $this->request_mock->shouldReceive('path')
                           ->twice()
                           ->withNoArgs()
                           ->andReturn('route');

        $this->filter->setRedirectRoute('route');

        $this->assertEquals(true, $this->filter->onRedirectPath($this->request_mock));

        $this->filter->setRedirectRoute('other_route');

        $this->assertEquals(false, $this->filter->onRedirectPath($this->request_mock));
    }
}
