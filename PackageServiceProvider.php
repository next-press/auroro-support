<?php

namespace Auroro\Support;

use App\Actions\ActionCollection;
use App\Blocks\BlockCollection;
use Auroro\Contracts\Application\CachesHooks;
use Spatie\LaravelPackageTools\PackageServiceProvider as LaravelPackageToolsPackageServiceProvider;

abstract class PackageServiceProvider extends LaravelPackageToolsPackageServiceProvider
{
    abstract public function configure(Package $package): void;

    public function configurePackage($package): void
    {
        $this->configure($package);
    }

    public function newPackage(): Package
    {
        return new Package();
    }

    /**
     * Load the given hooks file if the hooks are not already cached.
     *
     * @param  string  $path
     * @return void
     */
    protected function loadHooksFrom($path)
    {
        if (!($this->app instanceof CachesHooks && $this->app->hooksAreCached())) {
            require realpath($path);
        }
    }

    public function boot()
    {
        /** @var Package $package */
        $package = $this->package;

        /**
         * Register the hooks.
         */
        foreach ($package->hookFileNames as $hookFileName) {
            $this->loadHooksFrom("{$this->package->basePath('/../hooks/')}{$hookFileName}.php");
        }

        /**
         * Register the blocks.
         */
        foreach ($package->blocks as $blockClasses) {
            $this->registerBlock($blockClasses);
        }

        /**
         * Register the action.
         */
        foreach ($package->actions as $actionBlocks) {
            $this->registerAction($actionBlocks);
        }

        /**
         * Register the package so we can access it via the facade.
         */
        $this->app->singleton("ultimo.{$this->package->name}.package", function () {
            return $this->package;
        });

        parent::boot();
    }

    protected function registerAction($actionClass)
    {
        $action = $this->app->make($actionClass);

        $this->app
            ->make(ActionCollection::class)
            ->add($action);

        // If it also has a block, automatically registers it.
        if (method_exists($action, 'makeBlock')) {
            $actionBlock = $action->makeBlock();
            // clock($actionBlock);
            // register_workflow_action($actionBlock->getName(), $actionBlock->asVariation());
        }
    }

    /**
     * TODO: Move the repository registration to a separate service provider and class or package.
     */
    protected function registerBlock($blockClass)
    {
        $block = is_string($blockClass) ? $this->app->make($blockClass) : $blockClass;

        $this->app
            ->make(BlockCollection::class)
            ->add($block);
    }
}