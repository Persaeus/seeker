<?php

namespace Nihilsen\Seeker;

use Illuminate\Support\Arr;

class Config
{
    private static function get($config, $default = null)
    {
        $namespace = strtolower(class_basename(__NAMESPACE__));

        return config(
            "$namespace.$config",
            $default
        );
    }

    public static function directory(): ?string
    {
        return self::get('directory');
    }

    public static function endpoints(): ?array
    {
        if (is_null($value = self::get('endpoints'))) {
            return null;
        }

        return Arr::wrap($value);
    }

    public static function namespace(): string
    {
        return self::get('namespace');
    }

    public static function rateLimiter()
    {
        return self::get('rate_limiter');
    }
}
