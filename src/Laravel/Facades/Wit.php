<?php

namespace Jeylabs\Wit\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class Wit extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'wit';
    }
}
