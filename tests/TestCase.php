<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function instance($abstract, $instance)
    {
        $this->app->bind($abstract, fn () => $instance);

        return $instance;
    }
}
