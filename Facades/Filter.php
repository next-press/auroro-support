<?php

namespace Auroro\Support\Facades;

use Auroro\Support\Hook as SupportHook;
use Illuminate\Support\Facades\Facade;

class Filter extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SupportHook::class;
    }
}
