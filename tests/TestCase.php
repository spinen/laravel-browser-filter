<?php

namespace Tests\Spinen\BrowserFilter;

use Mockery;
use PHPUnit_Framework_TestCase;

/**
 * Class TestCase
 *
 * @package Tests\Spinen\BrowserFilter
 */
abstract class TestCase extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        if (class_exists('Mockery')) {
            Mockery::close();
        }

        parent::tearDown();
    }
}
