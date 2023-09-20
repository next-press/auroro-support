<?php

namespace Auroro\Support\Mixins;

use Auroro\Bridge\WordPress\AdminPages;
use Auroro\Support\AdminPage;
use Illuminate\Support\Str;

class AdminRouteMixin
{
    public function asAdmin()
    {
        return function (): AdminPage {
            $adminPages = app(AdminPages::class);

            /**
             * @var \Illuminate\Routing\Route $this
             */
            $pageSlug = Str::slug($this->uri);

            $adminPages->singletonIf($pageSlug, function ($container) {
                return $container->make(AdminPage::class, [
                    'route' => $this,
                ]);
            });

            /**
             * @var \Auroro\Support\AdminPage $adminPage
             */
            return $adminPages->get($pageSlug);
        };
    }

    public function getSlug()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getSlug();
        };
    }

    public function getTitle()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getTitle();
        };
    }

    public function getMenuTitle()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getMenuTitle();
        };
    }

    public function getCapability()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getCapability();
        };
    }

    public function getIcon()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getIcon();
        };
    }

    public function getPosition()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getPosition();
        };
    }

    public function getParent()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            return $this->asAdmin()->getParent();
        };
    }

    public function title()
    {
        return function (string $title) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->title($title);

            return $this;
        };
    }

    public function menuTitle()
    {
        return function (string $menuTitle) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->menuTitle($menuTitle);

            return $this;
        };
    }

    public function capability()
    {
        return function (string $capability) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->capability($capability);

            return $this;
        };
    }

    public function icon()
    {
        return function (string $icon) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->icon($icon);

            return $this;
        };
    }

    public function position()
    {
        return function (int $position) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->position($position);

            return $this;
        };
    }

    public function hidden()
    {
        return function () {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->hidden();

            return $this;
        };
    }

    public function parent()
    {
        return function ($parent) {
            /**
             * @var \Illuminate\Routing\Route $this
             */
            $this->asAdmin()->parent($parent);

            return $this;
        };
    }
}
