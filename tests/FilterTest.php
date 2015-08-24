<?php

namespace Tests\Spinen\BrowserFilter;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Mockery;
use Spinen\BrowserFilter\Support\ParserCreator;
use Tests\Spinen\BrowserFilter\Stubs\FilterStub as Filter;
use UAParser\Result\Client;
use UAParser\Result\Device;
use UAParser\Result\UserAgent;

/**
 * Class FilterTest
 *
 * @package Tests\Spinen\BrowserFilter\Route
 */
class FilterTest extends TestCase
{
    /**
     * @var Mockery\Mock
     */
    protected $cache_mock;

    /**
     * @var Mockery\Mock
     */
    protected $client_mock;

    /**
     * @var Mockery\Mock
     */
    protected $client_device_mock;

    /**
     * @var Mockery\Mock
     */
    protected $client_ua_mock;

    /**
     * @var Mockery\Mock
     */
    protected $config_mock;

    /**
     * @var Mockery\Mock
     */
    protected $detector_mock;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Mockery\Mock
     */
    protected $parser_mock;

    /**
     * @var Mockery\Mock
     */
    protected $redirect_response_mock;

    /**
     * @var Mockery\Mock
     */
    protected $redirector_mock;

    /**
     * @var Mockery\Mock
     */
    protected $request_mock;

    protected function setUp()
    {
        $this->setUpMocks();

        $this->filter = new Filter($this->cache_mock, $this->config_mock, $this->detector_mock, $this->parser_mock,
            $this->redirector_mock);

        parent::setUp();
    }

    protected function setUpMocks()
    {
        $this->cache_mock = Mockery::mock(Cache::class);

        $this->config_mock = Mockery::mock(Config::class);

        $agent = 'FakeBrowser/x.y (Spinen; S; PPC Mac OS X Mach-O; en; rv:a.b.c.d) Engine/YYYYMMDD Whatever/a.b.c';

        $this->detector_mock = Mockery::mock(Mobile_Detect::class);
        $this->detector_mock->shouldReceive('getUserAgent')
                            ->once()
                            ->withNoArgs()
                            ->andReturn($agent);

        $this->client_device_mock = Mockery::mock(Device::class);

        $this->client_ua_mock = Mockery::mock(UserAgent::class);

        $this->client_mock = Mockery::mock(Client::class);
        $this->client_mock->device = $this->client_device_mock;
        $this->client_mock->ua = $this->client_ua_mock;

        $this->parser_mock = Mockery::mock(ParserCreator::class);
        $this->parser_mock->shouldReceive('parseAgent')
                          ->once()
                          ->with($agent)
                          ->andReturn($this->client_mock);

        $this->request_mock = Mockery::mock(Request::class);

        $this->redirector_mock = Mockery::mock(Redirector::class);

        $this->redirect_response_mock = Mockery::mock(RedirectResponse::class);
    }

    private function returnGiven()
    {
        return function ($given) {
            return $given;
        };
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
    public function it_returns_the_value_from_the_implementing_class_process_method()
    {
        // In the stub, the process, method just returns "Stub"
        $this->assertEquals('Stub', $this->filter->handle($this->request_mock, $this->returnGiven()));
    }

    /**
     * @test
     */
    public function it_parses_the_third_parameter_and_causes_the_rules_to_be_set()
    {
        $rules = 'The rules';

        $this->filter->handle($this->request_mock, $this->returnGiven(), $rules);

        $this->assertEquals($rules, $this->filter->getRules());
    }

    /**
     * @test
     */
    public function it_parses_the_fourth_parameter_and_causes_the_redirect_route_to_be_set()
    {
        $redirect = 'The redirect';

        $this->filter->handle($this->request_mock, $this->returnGiven(), null, $redirect);

        $this->assertEquals($redirect, $this->filter->getRedirectRoute());
    }

    /**
     * @test
     */
    public function it_gets_the_correct_rules_for_the_device()
    {
        $rules = [
            'First' => [
                'Second' => [
                    '<=' => '3',
                    '>'  => '4',
                ],
                'Fifth'  => '*',
            ],
        ];

        $this->filter->parseFilterString($rules);

        $device = 'First';
        $this->client_device_mock->family = $device;

        $this->assertEquals($rules[$device], $this->filter->getBlockedBrowsers());
    }

    /**
     * @test
     */
    public function it_gets_the_correct_rules_for_the_browser()
    {
       $rules = [
            'First' => [
                'Second' => [
                    '<=' => '3',
                    '>'  => '4',
                ],
                'Fifth'  => '*',
            ],
        ];

        $this->filter->parseFilterString($rules);

        $device = 'First';
        $ua = 'Second';

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->assertEquals($rules[$device][$ua], $this->filter->getBlockedBrowserVersions());
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
}
