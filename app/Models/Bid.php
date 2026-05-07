<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Bid {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPdo();
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT * FROM bids");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function placeBid($auctionId, $userId, $amount, $auctionModel) {
        $auction = $auctionModel->findById($auctionId);
        if (!$auction || $auction['status'] !== 'active' || $auction['seller'] == $userId || $amount <= $auction['current_price'] + ($auction['bid_increment'] ?? 1.00)) {
            return false; // Invalid bid
        }
        $stmt = $this->pdo->prepare("INSERT INTO bids (auction_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$auctionId, $userId, $amount]);
        $this->pdo->prepare("UPDATE auctions SET current_price = ? WHERE id = ?")->execute([$amount, $auctionId]);
        return true;
    }
}