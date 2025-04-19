<?php

namespace Bastiaigner\LaravelXentral\Tests;

use Bastiaigner\LaravelXentral\LaravelXentralServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelXentralServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        return parent::getEnvironmentSetUp($app);
    }
}
