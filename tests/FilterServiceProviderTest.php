<?php

namespace Spinen\BrowserFilter;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mockery;

/**
 * Class FilterServiceProviderTest
 *
 * @package Spinen\BrowserFilter
 */
class FilterServiceProviderTest extends TestCase
{
    /**
     * @var Mockery\Mock
     */
    protected $application_mock;

    /**
     * @var Mockery\Mock
     */
    protected $config_mock;

    /**
     * @var ServiceProvider
     */
    protected $service_provider;

    protected function setUp(): void
    {
        $this->setUpMocks();

        $this->service_provider = new FilterServiceProvider($this->application_mock);

        parent::setUp();
    }

    protected function setUpMocks()
    {
        $this->config_mock = Mockery::mock(Config::class);
        $this->config_mock->shouldReceive('get')
                          ->withAnyArgs()
                          ->andReturn([]);
        $this->config_mock->shouldReceive('set')
                          ->withAnyArgs()
                          ->andReturnUndefined();

        $this->application_mock = Mockery::mock(Application::class);

        $this->application_mock = [
            'config'      => $this->config_mock,
            'path.config' => '/some/path',
        ];
    }

    /**
     * @test
     */
    public function it_can_be_constructed()
    {
        $this->assertInstanceOf(ServiceProvider::class, $this->service_provider);
    }

    /**
     * @test
     */
    public function it_does_nothing_in_the_register_method()
    {
        $this->assertNull($this->service_provider->register());
    }
}
