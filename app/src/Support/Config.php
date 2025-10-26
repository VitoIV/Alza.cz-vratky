<?php

namespace App\Support;

class Config
{
    protected static array $values = [];

    public static function load(string $defaultPath, ?string $customPath = null): void
    {
        if (!file_exists($defaultPath)) {
            throw new \RuntimeException('Default configuration file not found: '.$defaultPath);
        }
        $config = require $defaultPath;
        if (!is_array($config)) {
            throw new \RuntimeException('Configuration file must return an array: '.$defaultPath);
        }
        if ($customPath && file_exists($customPath)) {
            $customConfig = require $customPath;
            if (!is_array($customConfig)) {
                throw new \RuntimeException('Custom configuration file must return an array: '.$customPath);
            }
            $config = array_replace_recursive($config, $customConfig);
        }
        self::$values = $config;
        if (!empty($config['app']['timezone'])) {
            date_default_timezone_set($config['app']['timezone']);
        }
    }

    public static function get(string $key, $default = null)
    {
        $segments = explode('.', $key);
        $value = self::$values;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function all(): array
    {
        return self::$values;
    }
}
