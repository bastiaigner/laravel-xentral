<?php

namespace Bastiaigner\LaravelXentral\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bastiaigner\LaravelXentral\LaravelXentral
 */
class LaravelXentral extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bastiaigner\LaravelXentral\LaravelXentral::class;
    }
}
