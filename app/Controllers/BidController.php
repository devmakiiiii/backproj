<?php
namespace App\Controllers;

use App\Models\Bid;
use App\Models\Auction;
use App\Services\AuthService;

class BidController {
    private $bidModel;
    private $auctionModel;

    public function __construct() {
        $this->bidModel = new Bid();
        $this->auctionModel = new Auction();
    }

    public function placeBid() {
        header('Content-Type: application/json');
        $user = AuthService::verifyToken();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['item_id']) || !isset($input['bid_amount'])) {
            http_response_code(400);
            echo json_encode(['error' => 'item_id and bid_amount required']);
            return;
        }
        if ($this->bidModel->placeBid($input['item_id'], $user->user_id, $input['bid_amount'], $this->auctionModel)) {
            echo json_encode(['message' => 'Bid placed successfully']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid bid']);
        }
    }

    public function getItemBids($itemId) {
        header('Content-Type: application/json');
        $bids = $this->bidModel->getByItemId($itemId);
        echo json_encode(['bids' => $bids]);
    }
}