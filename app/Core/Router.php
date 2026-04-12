<?php
namespace App\Core;

class Router {
    private $routes = [];

    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
        if (strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        if (empty($uri)) $uri = '/';
        foreach ($this->routes[$method] ?? [] as $route => $handler) {
            if (preg_match('#^' . preg_replace('/\{([^}]+)\}/', '([^/]+)', $route) . '$#', $uri, $matches)) {
                array_shift($matches);  // Remove full match
                call_user_func_array($handler, $matches);
                return;
            }
        }
        http_response_code(404);
        echo "404 Not Found";
    }
}