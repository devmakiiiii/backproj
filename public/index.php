<?php
// Log errors to file instead of suppressing them
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
ini_set('display_errors', 0);

// Register default exception handler to return JSON errors
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
});

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
try {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->load();
} catch (Exception $e) {
    // If .env is missing, log and continue - will fail on DB connection
    error_log('.env loading failed: ' . $e->getMessage());
}

require_once __DIR__ . '/../config/config.php';

session_start();

$router = new App\Core\Router();

$auctionController = new App\Controllers\AuctionController();
$userController = new App\Controllers\UserController();
$adminController = new App\Controllers\AdminController();
$bidController = new App\Controllers\BidController();
$paymentController = new App\Controllers\PaymentController();
$webhookController = new App\Controllers\WebhookController();

$router->post('/api/webhook', [$webhookController, 'handle']);

$router->post('/api/payment/intent/{id}', [$paymentController, 'createPaymentIntent']);
$router->post('/api/payment/confirm/{id}', [$paymentController, 'confirmPayment']);
$router->post('/api/admin/promote/{id}', [$adminController, 'promoteToAdmin']);
$router->get('/api/admin/users', [$adminController, 'getAllUsers']);
$router->get('/api/admin/items', [$adminController, 'getAllItems']);
$router->get('/api/admin/bids', [$adminController, 'getAllBids']);
$router->get('/api/reports/highest-bids', [$adminController, 'getHighestBids']);
$router->get('/api/reports/auction-results', [$adminController, 'getAuctionResults']);

$router->post('/api/auth/register', [$userController, 'register']);
$router->post('/api/auth/login', [$userController, 'login']);
$router->post('/api/logout', [$userController, 'logout']);
$router->get('/api/users/profile', [$userController, 'getProfile']);
$router->put('/api/users/profile', [$userController, 'updateProfile']);

$router->post('/api/items', [$auctionController, 'create']);
$router->get('/api/items', [$auctionController, 'index']);
$router->get('/api/items/{id}', [$auctionController, 'show']);

$router->post('/api/bids', [$bidController, 'placeBid']);
$router->get('/api/bids/item/{item_id}', [$bidController, 'getItemBids']);

$router->get('/api/auctions', [$auctionController, 'index']);
$router->get('/api/auction/{id}', [$auctionController, 'show']);
$router->post('/api/auction', [$auctionController, 'create']);
$router->post('/api/auction/{id}/bid', [$auctionController, 'placeBid']);

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
