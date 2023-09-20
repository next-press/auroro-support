<?php

namespace Auroro\Support;

class Constant
{
    public static function get(string $key, $default = null)
    {
        return defined($key) ? constant($key) : $default;
    }
}
