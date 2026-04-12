<?php
header('Access-Control-Allow-Origin: *');
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

$router->post('/api/admin/close-auction/{id}', function($id) use ($auctionController) {
    $user = AuthService::verifyToken();
    if (!$user || $user->role !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }
    if ($auctionController->closeAuction($id)) {  // Add this method to AuctionController
        echo json_encode(['message' => 'Auction closed']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to close auction']);
    }
});

$router->dispatch();