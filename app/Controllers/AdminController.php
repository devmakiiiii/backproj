<?php
namespace App\Controllers;

use App\Models\User;
use App\Models\Auction;
use App\Models\Bid;
use App\Services\AuthService;
use App\Utils\Encryption;

class AdminController {
    private $userModel;
    private $auctionModel;
    private $bidModel;

    public function __construct() {
        $this->userModel = new User();
        $this->auctionModel = new Auction();
        $this->bidModel = new Bid();
    }

    public function promoteToAdmin($userId) {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || $user->role !== 'admin') {
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

    public function getAllItems() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || !$this->userModel->isAdmin($user->user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $items = $this->auctionModel->getActiveAuctions();
        echo json_encode(['items' => $items]);
    }

    public function getAllBids() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || !$this->userModel->isAdmin($user->user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $bids = $this->bidModel->all();
        foreach ($bids as &$bid) {
            if ($bid['amount_iv']) {
                $decrypted = Encryption::decrypt($bid['amount'], $bid['amount_iv'], $bid['amount_tag']);
                $bid['amount'] = $decrypted !== false ? $decrypted : $bid['amount'];
            }
            unset($bid['amount_iv'], $bid['amount_tag']);
        }
        echo json_encode(['bids' => $bids]);
    }

    public function getHighestBids() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || !$this->userModel->isAdmin($user->user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $stmt = \App\Core\Database::getInstance()->getPdo()->query("SELECT a.id, ai.title, MAX(b.amount) as highest_bid FROM auctions a JOIN auction_items ai ON a.item_id = ai.id LEFT JOIN bids b ON a.id = b.auction_id GROUP BY a.id");
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['results' => $results]);
    }

    public function getAuctionResults() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user || !$this->userModel->isAdmin($user->user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $stmt = \App\Core\Database::getInstance()->getPdo()->query("SELECT a.id, ai.title, a.final_price, a.status, u.username AS winner FROM auctions a JOIN auction_items ai ON a.item_id = ai.id LEFT JOIN users u ON a.winner_id = u.id WHERE a.status IN ('sold', 'ended')");
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['results' => $results]);
    }
}