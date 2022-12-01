<?php

namespace Spinen\BrowserFilter;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store as Session;
use Mobile_Detect;
use Mockery;
use Spinen\BrowserFilter\Support\ParserCreator;
use UAParser\Result\Client;
use UAParser\Result\Device;
use UAParser\Result\UserAgent;

/**
 * Class FilterCase
 */
abstract class FilterCase extends TestCase
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

    /**
     * @var Mockery\Mock
     */
    protected $session_mock;

    /**
     * Make a filter instance of the filter class under test.
     *
     * @return void
     */
    abstract protected function createFilter();

    /**
     * A closure that just returns what it is given.
     *
     * @return \Closure
     */
    protected function returnGiven()
    {
        return function ($given) {
            return $given;
        };
    }

    /**
     * Setup the test.
     */
    protected function setUp(): void
    {
        $this->setUpMocks();

        $this->createFilter();

        parent::setUp();
    }

    /**
     * Setup the mocks for the test.
     */
    protected function setUpMocks()
    {
        $this->cache_mock = Mockery::mock(Cache::class);

        $this->client_device_mock = Mockery::mock(Device::class);

        $this->client_ua_mock = Mockery::mock(UserAgent::class);

        $this->client_mock = Mockery::mock(Client::class);

        $this->client_mock->device = $this->client_device_mock;
        $this->client_mock->ua = $this->client_ua_mock;

        $this->config_mock = Mockery::mock(Config::class);

        $agent = 'FakeBrowser/x.y (Spinen; S; PPC Mac OS X Mach-O; en; rv:a.b.c.d) Engine/YYYYMMDD Whatever/a.b.c';

        $this->detector_mock = Mockery::mock(Mobile_Detect::class);

        $this->detector_mock->shouldReceive('getUserAgent')
                            ->once()
                            ->withNoArgs()
                            ->andReturn($agent);

        $this->parser_mock = Mockery::mock(ParserCreator::class);

        $this->parser_mock->shouldReceive('parseAgent')
                          ->once()
                          ->with($agent)
                          ->andReturn($this->client_mock);

        $this->request_mock = Mockery::mock(Request::class);

        $this->redirector_mock = Mockery::mock(Redirector::class);

        $this->redirect_response_mock = Mockery::mock(RedirectResponse::class);

        $this->session_mock = Mockery::mock(Session::class);

        $this->request_mock->shouldReceive('session')
                           ->withNoArgs()
                           ->andReturn($this->session_mock);
    }
}
