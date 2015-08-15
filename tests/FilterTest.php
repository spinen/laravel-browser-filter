<?php

namespace Tests\Spinen\BrowserFilter;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Mockery;
use Spinen\BrowserFilter\Filter;
use Spinen\BrowserFilter\ParserCreator;
use UAParser\Result\Client;
use UAParser\Result\Device;
use UAParser\Result\UserAgent;

/**
 * Class FilterTest
 *
 * @package Tests\Spinen\BrowserFilter
 */
class FilterTest extends TestCase
{
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

        $this->filter = new Filter($this->config_mock, $this->detector_mock, $this->parser_mock,
            $this->redirector_mock);

        parent::setUp();
    }

    protected function setUpMocks()
    {
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
    public function it_returns_the_request_when_nothing_is_not_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = null;
        $ua_config = null;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua)
                          ->andReturn($ua_config);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.route');

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->with('route');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_device_is_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = '*';
        $ua_config = null;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->withNoArgs()
                             ->never();

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.route')
                          ->andReturn('route');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_ua_is_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = [
            'Device',
        ];
        $ua_config = '*';

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->withNoArgs()
                             ->never();

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua)
                          ->andReturn($ua_config);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.route')
                          ->andReturn('route');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_version_is_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = '1.0.0';
        $device_config = [
            'Device',
        ];
        $ua_config = [
            '=' => '1.0.0',
        ];

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua)
                          ->andReturn($ua_config);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.route')
                          ->andReturn('route');

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_request_when_version_is_not_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = '1.0.8';
        $device_config = [
            'Device',
        ];
        $ua_config = [
            '<' => '1.0.0',
            '>=' => '2',
        ];

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua)
                          ->andReturn($ua_config);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.route');

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->with('route');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }
}
