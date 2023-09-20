<?php

namespace Auroro\Support\Contracts;

interface HasDeactivationHook
{
    public function onDeactivation(): void;
}