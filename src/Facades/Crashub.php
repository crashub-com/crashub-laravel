<?php

namespace Crashub\Facades;

use Illuminate\Support\Facades\Facade;

class Crashub extends Facade
{
    protected static function getFacadeAccessor() { return 'crashub'; }
}
