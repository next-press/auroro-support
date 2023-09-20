<?php

namespace Auroro\Support;

use Illuminate\Support\Str;
use Spatie\LaravelPackageTools\Package as LaravelPackageToolsPackage;

class Package extends LaravelPackageToolsPackage
{
    public array $hookFileNames = [];

    public array $blocks = [];

    public array $actions = [];

    public function shortName(): string
    {
        return Str::after($this->name, 'auroro-');
    }

    public function hasHook(string $hookFileName): static
    {
        $this->hookFileNames[] = $hookFileName;

        return $this;
    }

    public function hasHooks(...$hookFileNames): static
    {
        $this->hookFileNames = array_merge($this->hookFileNames, collect($hookFileNames)->flatten()->toArray());

        return $this;
    }

    public function hasAction(string $action): static
    {
        $this->actions[] = $action;

        return $this;
    }

    public function hasBlock(string $block): static
    {
        $this->blocks[] = $block;

        return $this;
    }

    public function hasBlocks(...$blocks): static
    {
        $this->blocks = array_merge($this->blocks, collect($blocks)->flatten()->toArray());

        return $this;
    }

    public function assetPath(string $path): string
    {
        $pathSegments = [
            'vendor',
            $this->name,
            $path,
        ];

        return app()->publicPath(
            implode('/', $pathSegments),
        );
    }

    public function asset(string $path): string
    {
        $pathSegments = [
            'vendor',
            $this->name,
            $path,
        ];

        return asset(
            implode('/', $pathSegments),
        );
    }
}
