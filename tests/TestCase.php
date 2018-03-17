<?php

namespace Spinen\BrowserFilter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Class TestCase
 *
 * @package Spinen\BrowserFilter
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;
}
