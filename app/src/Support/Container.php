<?php

namespace App\Support;

class Container
{
    private array $bindings = [];

    public function instance(string $key, $value): void
    {
        $this->bindings[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $this->bindings[$key] ?? $default;
    }
}
