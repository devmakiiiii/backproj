<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

$router = new App\Core\Router();

$auctionController = new App\Controllers\AuctionController();
$userController = new App\Controllers\UserController();

$router->get('/', function() {
    if (isset($_SESSION['user_id'])) {
        header('Location: /auctions');
    } else {
        header('Location: /login');
    }
});

$router->get('/login', [$userController, 'login']);
$router->post('/login', [$userController, 'login']);
$router->get('/signup', [$userController, 'signup']);
$router->post('/signup', [$userController, 'signup']);
$router->get('/logout', [$userController, 'logout']);

$router->get('/auctions', [$auctionController, 'index']);
$router->get('/auction/{id}', function($id) use ($auctionController) {
    $auctionController->show($id);
});
$router->get('/create-auction', [$auctionController, 'create']);
$router->post('/create-auction', [$auctionController, 'create']);

$router->dispatch();