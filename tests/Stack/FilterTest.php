<?php

namespace Tests\Spinen\BrowserFilter\Stack;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Mobile_Detect;
use Mockery;
use Spinen\BrowserFilter\Stack\Filter;
use Spinen\BrowserFilter\Support\ParserCreator;
use Tests\Spinen\BrowserFilter\TestCase;
use UAParser\Result\Client;
use UAParser\Result\Device;
use UAParser\Result\UserAgent;

/**
 * Class FilterTest
 *
 * @package Tests\Spinen\BrowserFilter\Stack
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
    public function it_returns_the_request_when_nothing_is_not_blocked_and_not_cached()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = null;
        $ua_config = null;
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             $cache_key,
                             false,
                             60,
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
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
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn(60);

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->with('route');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_device_is_blocked_and_not_cached()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = '*';
        $ua_config = null;
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             $cache_key,
                             $this->redirect_response_mock,
                             60,
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua);

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn(60);

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_ua_is_blocked_and_not_cached()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = [
            'Device',
        ];
        $ua_config = '*';
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             $cache_key,
                             $this->redirect_response_mock,
                             60,
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device)
                          ->andReturn($device_config);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua)
                          ->andReturn($ua_config);

        $this->config_mock->shouldReceive('get')
                          ->twice()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn(60);

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_version_is_blocked_and_not_cached()
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
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             $cache_key,
                             $this->redirect_response_mock,
                             60,
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
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
                          ->twice()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn(60);

        $this->redirector_mock->shouldReceive('route')
                              ->once()
                              ->with('route')
                              ->andReturn($this->redirect_response_mock);

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_request_when_version_is_not_blocked_and_not_cached()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = '1.0.8';
        $device_config = [
            'Device',
        ];
        $ua_config = [
            '<'  => '1.0.0',
            '>=' => '2',
        ];
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturnNull();

        $this->cache_mock->shouldReceive('put')
                         ->once()
                         ->withArgs([
                             $cache_key,
                             false,
                             60,
                         ])
                         ->andReturnNull();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->twice()
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
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.timeout')
                          ->andReturn(60);

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->with('route');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }

    /**
     * @test
     */
    public function it_does_not_redirect_on_the_redirect_route_even_though_client_is_blocked_and_not_cached()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = '*';
        $ua_config = null;
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->never()
                         ->withAnyArgs();

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withAnyArgs();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->withNoArgs()
                             ->never();

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->with('route');

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('route');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_request_when_the_client_is_cached_as_not_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = null;
        $ua_config = null;
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturn(false);

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withAnyArgs();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->withAnyArgs();

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(Request::class, $return);

        $this->assertEquals($this->request_mock, $return);
    }

    /**
     * @test
     */
    public function it_returns_the_redirect_when_the_client_is_cached_as_blocked()
    {
        $device = 'Device';
        $ua = 'Client';
        $version = 'a.b.c';
        $device_config = null;
        $ua_config = null;
        $cache_key = $device . ':' . $ua . ':' . $version;

        $this->client_device_mock->family = $device;
        $this->client_ua_mock->family = $ua;

        $this->cache_mock->shouldReceive('get')
                         ->once()
                         ->with($cache_key)
                         ->andReturn($this->redirect_response_mock);

        $this->cache_mock->shouldReceive('put')
                         ->never()
                         ->withAnyArgs();

        $this->client_ua_mock->shouldReceive('toVersion')
                             ->once()
                             ->withNoArgs()
                             ->andReturn($version);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device);

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.blocked.' . $device . '.' . $ua);

        $this->config_mock->shouldReceive('get')
                          ->once()
                          ->with('browserfilter.route')
                          ->andReturn('route');

        $this->config_mock->shouldReceive('get')
                          ->never()
                          ->with('browserfilter.timeout');

        $this->redirector_mock->shouldReceive('route')
                              ->never()
                              ->withAnyArgs();

        $this->request_mock->shouldReceive('path')
                           ->once()
                           ->withNoArgs()
                           ->andReturn('path');

        $return = $this->filter->handle($this->request_mock, $this->returnGiven());

        $this->assertInstanceOf(RedirectResponse::class, $return);

        $this->assertEquals($this->redirect_response_mock, $return);
    }
}
