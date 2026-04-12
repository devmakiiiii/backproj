<?php
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

session_start();

$router = new App\Core\Router();

$auctionController = new App\Controllers\AuctionController();
$userController = new App\Controllers\UserController();

// API routes only
$router->post('/api/signup', [$userController, 'signup']);
$router->post('/api/login', [$userController, 'login']);
$router->post('/api/logout', [$userController, 'logout']);

$router->get('/api/auctions', [$auctionController, 'index']);
$router->get('/api/auction/{id}', [$auctionController, 'show']);
$router->post('/api/auction', [$auctionController, 'create']);
$router->post('/api/auction/{id}/bid', [$auctionController, 'placeBid']);

$router->dispatch();