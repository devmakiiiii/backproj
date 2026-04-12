<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Auction;
use App\Services\AuthService;

class AdminController {
    private $userModel;
    private $auctionModel;

    public function __construct() {
        $this->userModel = new User();
        $this->auctionModel = new Auction();
    }

    public function promoteToAdmin($userId) {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || $user->role !== 'admin') {  // Fixed: was $user->id, now checks role
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        if ($this->userModel->updateRole($userId, 'admin')) {
            echo json_encode(['message' => 'User promoted to admin']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to promote user']);
        }
    }

    public function getAllUsers() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || !$this->userModel->isAdmin($user->user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $users = $this->userModel->getAllUsers();
        echo json_encode(['users' => $users]);
    }
}