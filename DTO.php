<?php

namespace Auroro\Support;

use CuyZ\Valinor\Cache\FileWatchingCache;
use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\MapperBuilder;
use Psr\SimpleCache\CacheInterface;

abstract class DTO
{
    public static function fromArray(array $data)
    {
        $mapper = new MapperBuilder();

        $cache = app()->make(CacheInterface::class);

        if (! app()->isProduction() || true) {
            $cache = new FileWatchingCache($cache);
        }

        // $mapper->withCache($cache);

        try {
            return $mapper
                ->mapper()
                ->map(
                    static::class,
                    $data,
                );
        } catch (MappingError $error) {
            // dd($error->getMessage());
        }
    }
}