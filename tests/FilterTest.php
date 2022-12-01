<?php

namespace Spinen\BrowserFilter;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spinen\BrowserFilter\Exceptions\FilterTypeNotSetException;
use Spinen\BrowserFilter\Exceptions\InvalidRuleDefinitionsException;
use Spinen\BrowserFilter\Stubs\FilterStub as Filter;

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

        $this->filter->setFilterAsAllowFilter();

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setFilterAsBlockFilter();

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
                    '>' => '4',
                ],
                'Other' => '*',
            ],
        ];

        $this->filter->setRulesForTest($rules);

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
                    '>' => '4',
                ],
                'Other' => '*',
            ],
        ];

        $this->filter->setRulesForTest($rules);

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
        $timeout = random_int(1, 100);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->assertEquals($timeout, $this->filter->getCacheTimeout());
    }

    /**
     * @test
     */
    public function it_knows_the_type_of_filter()
    {
        $this->filter->setFilterAsAllowFilter();

        $this->assertEquals('allow', $this->filter->getFilterType());

        $this->filter->setFilterAsBlockFilter();

        $this->assertEquals('block', $this->filter->getFilterType());
    }

    /**
     * @test
     */
    public function it_raises_exception_when_the_filter_type_has_not_been_set()
    {
        $this->expectException(FilterTypeNotSetException::class);

        $this->filter->getFilterType();
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

        $this->filter->setRedirectRouteForTest('set_route');

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_on_the_redirect_path()
    {
        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(true);

        $this->assertEquals($this->request_mock, $this->filter->handle($this->request_mock, $this->returnGiven()));
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_client_is_not_blocked_and_caches_the_result()
    {
        $timeout = random_int(1, 100);

        $this->filter->setFilterAsBlockFilter();

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 'Device:Browser:1.2.3',
                                 false,
                                 $timeout,
                             ]
                         )
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
                             ->withNoArgs()
                             ->andReturn('1.2.3');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn($timeout);

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $response);

        $this->assertEquals($this->request_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_result_from_the_next_filter_when_the_results_are_cached_as_not_blocked()
    {
        $this->filter->setFilterAsBlockFilter();

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
                          ->never()
                          ->with('browserfilter.route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $response);

        $this->assertEquals($this->request_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_from_the_filter_when_client_is_blocked_and_caches_the_result()
    {
        $timeout = random_int(1, 100);

        $this->filter->setFilterAsAllowFilter();

        $this->client_device_mock->family = 'Device';
        $this->client_ua_mock->family = 'Browser';

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with('Device:Browser:1.2.3')
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs(
                             [
                                 'Device:Browser:1.2.3',
                                 'route',
                                 $timeout,
                             ]
                         )
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
                          ->andReturn($timeout);

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $this->session_mock->shouldReceive('flash')
                           ->once()
                           ->withArgs(['redirected', true])
                           ->andReturnNull();

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals($this->redirect_response_mock, $response);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_the_client_is_cached_as_blocked()
    {
        $this->filter->setFilterAsAllowFilter();

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
                          ->never()
                          ->with('browserfilter.route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $this->session_mock->shouldReceive('flash')
                           ->once()
                           ->withArgs(['redirected', true])
                           ->andReturnNull();

        $response = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertEquals($this->redirect_response_mock, $response);
    }

    /**
     * @test
     */
    public function it_parses_the_third_parameter_and_causes_the_rules_to_be_set()
    {
        $timeout = random_int(1, 100);

        $this->filter->setFilterAsBlockFilter();

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
                          ->andReturn($timeout);

        $this->session_mock->shouldReceive('get')
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $rules = [
            'Device' => [],
        ];

        $this->filter->handle($this->request_mock, $this->returnGiven(), $rules);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_the_fourth_parameter_and_causes_the_redirect_route_to_be_set()
    {
        $this->filter->setFilterAsAllowFilter();

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
                          ->andReturn(random_int(1, 100));

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with($redirect)
                              ->andReturn($this->redirect_response_mock);

        $this->session_mock->shouldReceive('get')
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $this->session_mock->shouldReceive('flash')
                           ->withAnyArgs();

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'eq' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '=' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'eq' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'gt' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '>' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'gt' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ge' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '>=' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ge' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'lt' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'lt' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'le' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<=' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'le' => '1.2.2',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<>' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ne' => '1.2.4',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(true, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '!=' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    '<>' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->isMatchedBrowserVersion());

        $rules = [
            'Device' => [
                'Browser' => [
                    'ne' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setFilterAsBlockFilter();

        $rules = [];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setFilterAsBlockFilter();

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setFilterAsAllowFilter();

        $rules = [];

        $this->filter->setRulesForTest($rules);

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

        $this->filter->setFilterAsAllowFilter();

        $rules = [
            'Device' => [
                'Browser' => [
                    '==' => '1.2.3',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertEquals(false, $this->filter->needsRedirecting());
    }

    /**
     * @test
     */
    public function it_knows_if_the_request_is_to_a_redirect_path_so_that_the_filter_can_be_ignored()
    {
        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(true);

        $this->assertEquals(true, $this->filter->onRedirectPath($this->request_mock));

        $this->session_mock->shouldReceive('get')
                           ->once()
                           ->withArgs(['redirected', false])
                           ->andReturn(false);

        $this->assertEquals(false, $this->filter->onRedirectPath($this->request_mock));
    }

    /**
     * @test
     */
    public function it_validates_the_rules()
    {
        $rules = [
            'Device 1' => [
                'Browser' => [
                    '=' => '1',
                ],
            ],
            'Device 2' => [
                'Browser' => '*',
            ],
            'Device 3' => '*',
        ];

        $this->filter->setRulesForTest($rules);

        $this->assertNull($this->filter->validateRules());
    }

    /**
     * @test
     */
    public function it_raises_exception_when_devices_are_misconfigured_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device',
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_devices_are_not_an_array_or_asterisk_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device' => 2,
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_browsers_are_misconfigured_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device' => [
                'Browser',
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_browsers_are_not_an_array_or_asterisk_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device' => [
                'Browser' => '2',
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_versions_are_misconfigured_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device' => [
                'Browser' => [
                    '2',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }

    /**
     * @test
     */
    public function it_raises_exception_when_versions_operators_are_misconfigured_in_the_rules()
    {
        $this->expectException(InvalidRuleDefinitionsException::class);

        $rules = [
            'Device' => [
                'Browser' => [
                    '~' => '2',
                ],
            ],
        ];

        $this->filter->setRulesForTest($rules);

        $this->filter->validateRules();
    }
}
