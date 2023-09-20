<?php

namespace Auroro\Support;

use Auroro\Bootstrap\LoadConfiguration as BootstrapLoadConfiguration;
use Auroro\Bootstrap\LoadEnvironmentVariables as BootstrapLoadEnvironmentVariables;
use Auroro\Support\Concerns\HasHooks;
use Auroro\Bridge\Laravel\HookBag as LaravelHookBag;
use Auroro\Bridge\WordPress\Config as WordPressConfig;
use Auroro\Bridge\WordPress\HookBag as WordPressHookBag;
use Auroro\Contracts\Bridge\Config;
use Auroro\Contracts\Bridge\HookBag;
use Auroro\Contracts\Runtime\PathResolver;
use Auroro\Contracts\Runtime\UrlResolver;
use Auroro\Foundation\PositionalServiceProviders;
use Auroro\Runtime\WordPress\PathResolver as WordPressPathResolver;
use Auroro\Runtime\WordPress\UrlResolver as WordPressUrlResolver;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application as FoundationApplication;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\PackageManifest;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;

abstract class Plugin extends Application
{
    use HasHooks;

    /**
     * The root file of the plugin.
     *
     * In a number of different places, WordPress uses the plugin file to keep
     * track of the plugin location. This is the file that is used to register
     * the plugin with WordPress. It gets passed to the create method.
     *
     * @see https://developer.wordpress.org/plugins/plugin-basics/
     *
     * @since 0.1.0
     * @var string
     */
    protected string $pluginFile;

    /**
     * Indicates if the application has been created before.
     *
     * @var bool
     */
    protected $hasBeenCreated = false;

    /**
     * The base plugin URL for the application.
     *
     * @var string
     */
    protected $pluginUrl;

    /**
     * The bootstrap classes for the application.
     *
     * On a regular Laravel application, this is defined in the Kernel class.
     * Inside a plugin, we need to define it here, as we boot the app as soon as it's created.
     *
     * @var string[]
     */
    protected $bootstrappers = [
        \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
        \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
        \Illuminate\Foundation\Bootstrap\HandleExceptions::class,
        \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
        \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
        \Illuminate\Foundation\Bootstrap\BootProviders::class,
        \Auroro\Bootstrap\LoadCLICommand::class,
        \Auroro\Bootstrap\BootUpdater::class,
    ];

    /**
     * Allow the child plugins to bind their own core classes.
     *
     * Here, each plugin can bind the core interfaces, which are generally
     * found on the original Laravel bootstrap/app file. Here, plugins
     * are expected to bind a HTTP kernel, a console kernel and an
     * exception handler.
     *
     * @since 0.1.0
     * @return void
     */
    abstract public function prepare(): void;

    /**
     * Create a new Auroro application/plugin instance.
     *
     * @since 0.1.0
     *
     * @param string $rootFile The root file of the plugin, usually __FILE__.
     * @param string $namespace The namespace of the plugin.
     *
     * @return FoundationApplication
     * @throws BindingResolutionException
     * @throws InvalidArgumentException
     */
    public static function create(string $rootFile, string $namespace = 'App\\'): FoundationApplication
    {
        return static::getInstance()
            ->autoRegister()
            ->registerRuntimeBindings()
            ->useNamespace($namespace)
            ->useRootFile($rootFile)
            ->maybeInitialize();
    }

    /**
     * Register the runtime bindings into the container.
     *
     * This method is called before the application is initialized.
     *
     * @since 0.1.0
     * @return static
     */
    protected function registerRuntimeBindings(): static
    {
        $this->bind(
            PathResolver::class,
            WordPressPathResolver::class,
        );

        $this->bind(
            UrlResolver::class,
            WordPressUrlResolver::class,
        );

        return $this;
    }

    /**
     * Register the core static instance of the plugin into the container.
     *
     * @return static
     * @throws BindingResolutionException
     */
    protected function autoRegister(): static
    {
        $this->instance(Plugin::class, $this);

        return $this;
    }

    /**
     * Get the bootstrap classes for the application.
     *
     * @return array
     */
    protected function bootstrappers()
    {
        return $this->bootstrappers;
    }

    /**
     * Sets the namespace we will use for the plugin, since we can't use the
     * composer.json file to derive it, like it is done in Laravel.
     *
     * @param string $namespace The namespace of the plugin. Something like App\\.
     * @return static
     */
    protected function useNamespace(string $namespace): static
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Returns the string received scoped by the plugin slug and a dot separator.
     *
     * @param string $string String to brand. Example: 'config'.
     * @return string The string branded. Example: 'my-plugin.database.wordpress.config'.
     */
    public function brand(string $string): string
    {
        return collect([
            $this['config']->get('auroro.slug', 'auroro'),
            $string,
        ])->filter()->join('.');
    }

    protected function registerPositionalProviders()
    {
        /**
         * Register this on the container, so we can use it later.
         */
        $this->singleton(PositionalServiceProviders::class, function () {
            return new PositionalServiceProviders;
        });

        $this->alias(PositionalServiceProviders::class, 'auroro.positional_providers');

        $this->doAction(
            $this->brand("providers.register"),
            $this,
        );

        return $this->make(PositionalServiceProviders::class);
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $providers = Collection::make(
            $this->make('config')->get('app.providers')
        )->partition(fn ($provider) => str_starts_with($provider, 'Illuminate\\'));

        $positionalProviders = $this->registerPositionalProviders();

        $providers->splice(1, 0, [
            $this->make(PackageManifest::class)->providers(),
            $positionalProviders->get('core')
        ]);

        $providers->push($positionalProviders->get('app'));

        (new ProviderRepository(
            $this,
            new Filesystem,
            $this->getCachedServicesPath()
        ))->load($providers->collapse()->toArray());
    }

    /**
     * Sets the root file of the plugin.
     *
     * WordPress uses the plugin file to keep track of the plugin location.
     * We also need to pass it when tapping into different WordPress hooks.
     * For that reason, we need to set it as soon as the plugin is created.
     *
     * @return static
     */
    protected function useRootFile(string $rootFile): static
    {
        /**
         * Set the root file of the plugin.
         */
        $this->rootFile = $rootFile;

        /**
         * Add the root file to the container,
         * in case we need it later.
         */
        $this->instance('auroro.root_file', $this->rootFile);

        /**
         * Finally, we use the dirname of the root file as the base path.
         * That way we can tell Laravel where to find the plugin files,
         * more or less like it is done in a regular Laravel application.
         *
         * From Laravel's perspective, the plugin is the entire application,
         * and the plugin root folder is the root folder of the application.
         */
        $this->setBasePath(dirname($this->rootFile));

        return $this;
    }

    /**
     * Returns the root file of the plugin.
     *
     * @return string
     */
    public function getRootFile(): string
    {
        return $this->rootFile;
    }

    /**
     * Bind all of the application paths in the container.
     *
     * For most of the paths, we can use the same logic as Laravel.
     *
     * The only place where it makes an actual difference is the storage path.
     * In a regular Laravel application, the storage path is calculated based
     * on the root path. In a plugin, we want to scope the storage path by
     * the plugin name, so we can have multiple plugins running on the same
     * WordPress installation.
     *
     * Since each "runtime" has a different storage path, we inject the
     * resolver, depending on whatever detection we've made.
     *
     * @since 0.1.0
     * @return void
     */
    protected function bindPathsInContainer()
    {
        parent::bindPathsInContainer();

        /**
         * Use the runtime storage path resolver to calculate the storage path.
         *
         * We do things this way because it allows us to replace the resolver
         * with a Laravel-based one, to run a pure Laravel application instead
         * of a WordPress environment.
         *
         * @var PathResolver $pathResolver
         */
        $this->useStoragePath(
            $this->make(PathResolver::class)->storagePath()
        );

        /**
         * Use the runtime URL resolver to calculate the plugin URL.
         *
         * We do things this way because it allows us to replace the resolver
         * with a Laravel-based one, to run a pure Laravel application instead
         * of a WordPress environment.
         *
         * @var UrlResolver $urlResolver
         */
        $this->usePluginUrl(
            $this->make(UrlResolver::class)->publicUrl()
        );
    }

    /**
     * Sets the base plugin URL for the application.
     *
     * @param string $pluginUrl The plugin URL.
     * @return static
     */
    public function usePluginUrl(string $pluginUrl): static
    {
        $this->pluginUrl = $pluginUrl;

        return $this;
    }

    /**
     * Returns the base plugin URL for the application.
     *
     * @param string $path The URL to the plugin base folder.
     * @return string
     */
    public function pluginUrl(string $path = ''): string
    {
        return $this->pluginUrl . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Returns the file path of the plugin.
     *
     * @return static
     */
    protected function maybeInitialize(): static
    {
        if ($this->hasBeenCreated) return $this;

        $this->hasBeenCreated = true;

        return $this->initialize();
    }

    protected function onPluginsLoaded($callback, $priority = 10)
    {
        return function_exists('add_action')
            ? add_action('plugins_loaded', $callback, $priority)
            : $callback();
    }

    /**
     * Initializes the plugin.
     *
     * @return static
     */
    protected function initialize(): static
    {
        $this->onPluginsLoaded(function() {

            $this->prepare();

            $this->instance('request', Request::capture());

            Facade::clearResolvedInstance('request');

            $this->bootstrapWith($this->bootstrappers());

        }, 0);

        return $this;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        parent::registerBaseBindings();

        /*
        |--------------------------------------------------------------------------
        | Bind Bridge Interfaces
        |--------------------------------------------------------------------------
        |
        | Next, we need to bind some important interfaces into the container so
        | we will be able to resolve them when needed. The kernels serve the
        | incoming requests to this application from both the web and CLI.
        |
        */

        $this->singleton(
            HookBag::class,
            function ($app) {
                return $app->make(
                    $app->runningInConsole()
                        ? LaravelHookBag::class
                        : WordPressHookBag::class
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | The Application Configuration
        |--------------------------------------------------------------------------
        |
        | Next, we need to bind some important interfaces into the container so
        | we will be able to resolve them when needed. The kernels serve the
        | incoming requests to this application from both the web and CLI.
        |
        */

        $this->singleton(
            Config::class,
            $this->runningInConsole()
                ? WordPressConfig::class
                : WordPressConfig::class
        );

        /**
         * Alias the default config class to the config interface.
         *
         * @since 0.1.0
         */
        $this->alias(Config::class, 'default_config');

        /*
        |--------------------------------------------------------------------------
        | Rebind the LoadEnvironmentVariables class
        |--------------------------------------------------------------------------
        |
        | Next, we need to bind some important interfaces into the container so
        | we will be able to resolve them when needed. The kernels serve the
        | incoming requests to this application from both the web and CLI.
        |
        */

        $this->bind(
            LoadEnvironmentVariables::class,
            BootstrapLoadEnvironmentVariables::class,
        );

        /*
        |--------------------------------------------------------------------------
        | Rebind the LoadConfiguration class
        |--------------------------------------------------------------------------
        |
        | Next, we need to bind some important interfaces into the container so
        | we will be able to resolve them when needed. The kernels serve the
        | incoming requests to this application from both the web and CLI.
        |
        */

        $this->bind(
            LoadConfiguration::class,
            BootstrapLoadConfiguration::class,
        );
    }

    /**
     * Calls a callable with positional arguments, instead of named ones.
     *
     * This is useful when passing hooks and filters intended for WordPress
     * through the Laravel container. WordPress hooks and filters use
     * positional arguments, while Laravel uses named ones. This method
     * bridges the gap between the two.
     *
     * @see \Illuminate\Container\Container::call()
     *
     * @param mixed $callback The callable to call.
     * @param array $parameters The parameters to pass to the callable.
     * @param mixed $defaultMethod The method to call if the callable is a class. Defaults to 'handle'.
     *
     * @return mixed
     */
    public function callWithPositionalArguments($callback, array $parameters = [], $defaultMethod = null)
    {
        $values = $parameters['values'] ?? [];

        /**
         * The strategy here is somewhat simple: we try to call the callable
         * with the given parameters.
         *
         * If it fails, we use the generated exception to find out which
         * dependency is missing. We'll need the name to push it to the
         * parameters array, with the first value of the values array.
         *
         * Rinse and repeat until we can call the callable. If there
         * are no values left, we throw the exception, as we can't
         * resolve the dependency.
         *
         * @since 0.1.0
         */
        while (true) {
            try {
                return $this->call($callback, $parameters, $defaultMethod);
            } catch (BindingResolutionException $th) {
                $missingDependency = preg_replace(
                    '/^(?:.*)\[(?:.*)\[ <required> \$([^\]]+) \]\] in class.*$/',
                    '$1',
                    $th->getMessage()
                );

                if (!$values) {
                    throw $th;
                }

                $parameters[$missingDependency] = array_shift($values);
            }
        }
    }
}