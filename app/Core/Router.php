<?php
class Router {
    private array $routes = [];

    public function get(string $path, array $handler): void { $this->routes['GET'][$path] = $handler; }
    public function post(string $path, array $handler): void { $this->routes['POST'][$path] = $handler; }

    public function dispatch(string $method, string $path): void {
        $handler = $this->routes[$method][$path] ?? null;
        if (!$handler) {
            http_response_code(404);
            require __DIR__ . '/../Views/erro/404.php';
            return;
        }
        [$class, $action] = $handler;
        (new $class)->$action();
    }
}
