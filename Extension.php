<?php

namespace Auroro\Support;

use Auroro\Support\Concerns\HasHooks;

class Extension
{
    use HasHooks;

    public function __construct(
        protected string $appSlug,
        protected string $rootFile
    ) {
        // Silence is golden.
    }

    public static function create(string $appSlug, string $rootFile): Extension
    {
        return new static($appSlug, $rootFile);
    }

    public function connector()
    {
        return function_exists('add_action') ? 'add_action' : [$this, 'on'];
    }

    public function with(string $provider, string $location = 'app'): static
    {
        call_user_func($this->connector(), "{$this->appSlug}.providers.register", function ($app) use ($provider, $location) {
            $providers = $app->make('auroro.positional_providers');
            $providers[$location]->push($provider);
        });

        return $this;
    }
}
