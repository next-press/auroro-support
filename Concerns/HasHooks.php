<?php

namespace Auroro\Support\Concerns;

use Auroro\Support\Facades\Hook;

trait HasHooks {

    protected function on($hook, ...$args)
    {
        return Hook::on($hook, ...$args);
    }

    protected function applyFilters($hook, ...$args)
    {
        return Hook::applyFilters($hook, ...$args);
    }

    protected function doAction($hook, ...$args)
    {
        return Hook::doAction($hook, ...$args);
    }

}
