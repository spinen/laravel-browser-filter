<?php

namespace Spinen\BrowserFilter;

use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Class TestCase
 *
 * @package Spinen\BrowserFilter
 */
abstract class TestCase extends PHPUnitTestCase
{
    public function tearDown()
    {
        if (class_exists('Mockery')) {
            Mockery::close();
        }

        parent::tearDown();
    }
}
