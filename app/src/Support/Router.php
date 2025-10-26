<?php

namespace App\Support;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][] = ['pattern' => $path, 'handler' => $handler];
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][] = ['pattern' => $path, 'handler' => $handler];
    }

    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $routes = $this->routes[$method] ?? [];
        foreach ($routes as $route) {
            $pattern = $route['pattern'];
            if (strpos($pattern, '(') !== false) {
                $regex = '#^'.$pattern.'$#';
                if (preg_match($regex, $path, $matches)) {
                    array_shift($matches);
                    return call_user_func_array($route['handler'], $matches);
                }
            } else {
                if ($pattern === $path) {
                    return call_user_func($route['handler']);
                }
            }
        }

        http_response_code(404);
        echo '404 Not Found';
        return null;
    }
}
