<?php
// Log errors to file instead of suppressing them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// CORS headers for preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400'); // 24 hours
    exit(0);
}

// Regular CORS headers for all requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

session_start();

$router = new App\Core\Router();

$auctionController = new App\Controllers\AuctionController();
$userController = new App\Controllers\UserController();
$adminController = new App\Controllers\AdminController();
$paymentController = new App\Controllers\PaymentController();
$webhookController = new App\Controllers\WebhookController();

$router->post('/api/webhook', [$webhookController, 'handle']);

$router->post('/api/payment/intent/{id}', [$paymentController, 'createPaymentIntent']);
$router->post('/api/payment/confirm/{id}', [$paymentController, 'confirmPayment']);
$router->post('/api/admin/promote/{id}', [$adminController, 'promoteToAdmin']);
$router->get('/api/admin/users', [$adminController, 'getAllUsers']);
$router->post('/api/signup', [$userController, 'signup']);
$router->post('/api/login', [$userController, 'login']);
$router->post('/api/logout', [$userController, 'logout']);

$router->get('/api/auctions', [$auctionController, 'index']);
$router->get('/api/auction/{id}', [$auctionController, 'show']);
$router->post('/api/auction', [$auctionController, 'create']);
$router->post('/api/auction/{id}/bid', [$auctionController, 'placeBid']);
$router->get('/api/user/profile', function() use ($userController) {
    header('Content-Type: application/json');
    try {
        $user = \App\Services\AuthService::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $userData = $userController->getUserById($user->user_id);
        if ($userData) {
            echo json_encode([
                'id' => $user->user_id,
                'firstname' => $userData['firstname'] ?? '',
                'lastname' => $userData['lastname'] ?? '',
                'email' => $userData['email'] ?? 'user@example.com',
                'role' => $user->role
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
});

$router->post('/api/admin/close-auction/{id}', function($id) use ($auctionController) {
    header('Content-Type: application/json');
    try {
        $user = \App\Services\AuthService::verifyToken();
        if (!$user || $user->role !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        if ($auctionController->closeAuction($id)) {
            echo json_encode(['message' => 'Auction closed']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Failed to close auction']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
});

$router->dispatch();
