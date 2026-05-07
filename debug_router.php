<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();

// Let's manually test the router logic
$method = $_SERVER['REQUEST_METHOD'] = 'POST';
$uri = parse_url($_SERVER['REQUEST_URI'] = '/api/login', PHP_URL_PATH);
$base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] = '/index.php'));
echo "REQUEST_URI: {$_SERVER['REQUEST_URI']}\n";
echo "SCRIPT_NAME: {$_SERVER['SCRIPT_NAME']}\n";
echo "parsed uri: $uri\n";
echo "base: $base\n";
if (strpos($uri, $base) === 0) {
    $uri = substr($uri, strlen($base));
    echo "after base removal: $uri\n";
}
if (empty($uri)) $uri = '/';
echo "final uri: $uri\n";

// Now let's see what routes are registered by creating a test router
$router = new App\Core\Router();
$userController = new App\Controllers\UserController();
$router->post('/api/login', [$userController, 'login']);

// Since routes is private, let's use reflection to access it
$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);

echo "\nRoutes:\n";
print_r($routes);

// Try to match using the router's logic
echo "\nTrying to match route...\n";
if (isset($routes[$method] [$uri])) {
    echo "Route found for $method $uri\n";
} else {
    echo "No exact route found for $method $uri\n";
    // The router uses preg_match, so let's check that way too
    foreach ($routes[$method] ?? [] as $route => $handler) {
        if (preg_match('#^' . preg_replace('/\{([^}]+)\}/', '([^/]+)', $route) . '$#', $uri, $matches)) {
            echo "Route matched via preg_match: $route\n";
            break;
        }
    }
}
?>