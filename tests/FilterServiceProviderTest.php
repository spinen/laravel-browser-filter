<?php

namespace Spinen\BrowserFilter;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Mockery;

/**
 * Class FilterServiceProviderTest
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

        // TODO: Allow mocking out the "configurationIsCached" method & be an array
        // $this->application_mock = Mockery::mock(Application::class);
        // $this->application_mock->shouldReceive('configurationIsCached')
        //                        ->withNoArgs()
        //                        ->andReturnTrue();

        $this->application_mock = [
            'config' => $this->config_mock,
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
        $this->markTestSkipped('Need to figure out how to allow application be a mock & an array');

        $this->assertNull($this->service_provider->register());
    }
}
