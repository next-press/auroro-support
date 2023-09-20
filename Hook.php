<?php

namespace Auroro\Support;

use ArrayAccess;
use Auroro\Contracts\Bridge\Hook as BridgeHook;
use Auroro\Contracts\Bridge\HookBag;
use Auroro\Hooks\Values;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use OutOfRangeException;

class HookArgs extends Collection
{
}

class Hook implements BridgeHook
{
    protected $additionalParameters = [];

    public function __construct(
        public readonly mixed $name = '',
        public readonly mixed $callback = null,
        public readonly int $priority = 10,
        public readonly int $acceptedArgs = 1,
        public ?string $waitUntil = null,
    ) {
    }

    public static function on($name, $callback, int $priority = 10, int $acceptedArgs = 3, ?string $waitUntil = null): BridgeHook
    {
        $hook = app()->make(static::class, compact([
            'name',
            'callback',
            'priority',
            'acceptedArgs',
            'waitUntil',
        ]));

        app(HookBag::class)->add($hook);

        return $hook;
    }

    public function decoratedCallback()
    {
        return function (...$args) {
            $params = [
                'values' => $args,
                ...$this->additionalParameters,
            ];

            app()->instance(Values::class, Values::from($params['values'], false));

            if ((is_string($this->callback) || is_object($this->callback)) && method_exists($this->callback, 'runUnless')) {
                $instance = app()->make($this->callback);

                if (app()->call([$instance, 'runUnless'])) {

                    /**
                     * We don't quite know if this is being called by an action or a filter.
                     * So we'll return the first argument, just in case.
                     *
                     * An action would simply ignore it, while the filter would receive the
                     * value it originally had.
                     */
                    return $args[0] ?? null;
                }
            }

            return app()->callWithPositionalArguments($this->callback, $params);
        };
    }

    public function with(...$params)
    {
        $this->additionalParameters = array_merge(
            $this->additionalParameters,
            $params,
        );

        return $this;
    }

    public function waitUntil(string $hook): BridgeHook
    {
        $this->waitUntil = $hook;

        return $this;
    }

    public function applyFilters($name, ...$args)
    {
        if (function_exists('apply_filters')) {
            return apply_filters($name, ...$args);
        }

        return $args[0];
    }

    public function doAction($name, ...$args)
    {
        if (function_exists('do_action')) {
            do_action($name, ...$args);
        }

        return $this;
    }

    public function allFilters()
    {
        return json_decode(file_get_contents(base_path('vendor/wp-hooks/wordpress-core/hooks/filters.json')));
    }

    public function allActions()
    {
        // dd(ABSPATH.('../vendor/wp-hooks/wordpress-core/hooks/actions.json'));
        return json_decode(file_get_contents(base_path('vendor/wp-hooks/wordpress-core/hooks/actions.json')));
    }
}

class PositionalParameters implements ArrayAccess
{
    const NOT_FOUND = 'not-found';

    protected array $items = [];

    protected int $yeldCount = 0;

    protected function get($key)
    {
        $value = $this->items[$key] ?? static::NOT_FOUND;

        if ($value === static::NOT_FOUND) {
            $value = $this->items[$this->yeldCount] ?? static::NOT_FOUND;
            $this->yeldCount++;
        }

        return $value;
    }

    protected function hasValue($value)
    {
        return $value !== static::NOT_FOUND;
    }

    protected function throwIfNotFound($value)
    {
        if ($value === static::NOT_FOUND) {
            throw new OutOfRangeException('Value not found');
        }
    }

    public function __construct($items = [])
    {
        $this->items = $items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->hasValue($this->get($offset));
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$this->yeldCount]);
        $this->yeldCount++;
    }
}