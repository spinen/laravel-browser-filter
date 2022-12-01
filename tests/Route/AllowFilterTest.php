<?php

namespace Spinen\BrowserFilter\Route;

use Spinen\BrowserFilter\FilterCase;
use Spinen\BrowserFilter\Route\AllowFilter as Filter;

/**
 * Class FilterTest
 */
class AllowFilterTest extends FilterCase
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
    public function it_sets_the_filter_type_to_allow()
    {
        $this->assertEquals('allow', $this->filter->getFilterType());
    }
}
