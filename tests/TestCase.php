<?php

namespace Bastiaigner\LaravelXentral\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Bastiaigner\LaravelXentral\LaravelXentralServiceProvider;

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
