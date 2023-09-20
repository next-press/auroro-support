<?php

namespace Auroro\Support\Contracts;

interface HasActivationHook
{
    public function onActivation(): void;
}