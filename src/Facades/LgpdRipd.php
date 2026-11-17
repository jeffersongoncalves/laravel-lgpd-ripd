<?php

namespace JeffersonGoncalves\LgpdRipd\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\LgpdRipd\LgpdRipd
 */
class LgpdRipd extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-lgpd-ripd';
    }
}
