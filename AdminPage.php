<?php

namespace Auroro\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Str;

class AdminPage
{
    const HIDDEN_ADMIN_PAGE_PARENT = 'invalid-parent';

    protected string $slug;

    protected string $title;

    protected string $menuTitle;

    protected string $capability;

    protected string $iconUrl;

    protected int $position;

    protected $parent = null;

    public function __construct(
        protected Route $route,
    ) {
        $this->slug = str_replace('/', '.', $this->route->uri);
        $this->title = $this->generateTitleFromRouteUri();
        $this->menuTitle = $this->generateTitleFromRouteUri();
        $this->capability = 'manage_options';
        $this->iconUrl = 'dashicons-admin-site-alt3';
        $this->position = 100;
    }

    protected function generateTitleFromRouteUri(): string
    {
        $titleSegment = Str::afterLast($this->route->uri(), '/');

        return Str::headline($titleSegment);
    }

    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function hidden()
    {
        $this->parent = self::HIDDEN_ADMIN_PAGE_PARENT;

        return $this;
    }

    public function menuTitle(string $menuTitle): self
    {
        $this->menuTitle = $menuTitle;

        return $this;
    }

    public function capability(string $capability): self
    {
        $this->capability = $capability;

        return $this;
    }

    public function icon(string $iconUrl): self
    {
        $this->iconUrl = $iconUrl;

        return $this;
    }

    public function position(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function parent($parent): self
    {
        $this->parent = is_a($parent, Route::class)
            ? (string) $parent->asAdmin()
            : $parent;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getMenuTitle(): string
    {
        return $this->menuTitle;
    }

    public function getCapability(): string
    {
        return $this->capability;
    }

    public function getIcon(): string
    {
        return $this->iconUrl;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getParent(): string|null
    {
        return $this->parent;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function __toString()
    {
        return $this->getSlug();
    }
}
