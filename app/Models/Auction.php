<?php
namespace App\Models;

use App\Core\Database;
use PDO;
use Exception;

class Auction {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPdo();
    }

    public function getActiveAuctions($filters = []) {
        $query = "SELECT a.id, ai.title, a.current_price, a.end_time, c.name AS category FROM auctions a JOIN auction_items ai ON a.item_id = ai.id LEFT JOIN categories c ON ai.category_id = c.id WHERE a.status = 'active' AND a.end_time > NOW()";
        $params = [];
        if (!empty($filters['search'])) {
            $query .= " AND ai.title LIKE ?";
            $params[] = "%{$filters['search']}%";
        }
        if (!empty($filters['category'])) {
            $query .= " AND ai.category_id = ?";
            $params[] = $filters['category'];
        }
        $query .= " ORDER BY a.end_time ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT a.*, ai.title, ai.description, ai.image_url, u.username AS seller FROM auctions a JOIN auction_items ai ON a.item_id = ai.id JOIN users u ON ai.seller_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCategories() {
        $stmt = $this->pdo->query("SELECT id, name FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBids($auctionId) {
        $stmt = $this->pdo->prepare("SELECT b.amount, u.username, b.created_at FROM bids b JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.amount DESC");
        $stmt->execute([$auctionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data, $userId) {
        $stmt = $this->pdo->prepare("INSERT INTO auction_items (seller_id, category_id, title, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $data['category_id'], $data['title'], $data['description']]);
        $itemId = $this->pdo->lastInsertId();

        $stmt2 = $this->pdo->prepare("INSERT INTO auctions (item_id, starting_price, current_price, end_time) VALUES (?, ?, ?, ?)");
        $stmt2->execute([$itemId, $data['starting_price'], $data['starting_price'], $data['end_time']]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        // Implement update logic, e.g., for title, description, etc.
        // But since auctions are immutable, perhaps only allow if not started
    }

    public function delete($id) {
        // Only allow delete if no bids and not active
    }

    public function placeBid($auctionId, $userId, $amount) {
        $auction = $this->findById($auctionId);
        if (!$auction || $auction['status'] !== 'active' || $auction['seller'] == $userId || $amount <= $auction['current_price'] + ($auction['bid_increment'] ?? 1.00)) {
            return false; // Invalid bid
        }
        $stmt = $this->pdo->prepare("INSERT INTO bids (auction_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$auctionId, $userId, $amount]);
        $this->pdo->prepare("UPDATE auctions SET current_price = ? WHERE id = ?")->execute([$amount, $auctionId]);
        return true;
    }

    public function endAuctions() {
        // Refactored from original
        $stmt = $this->pdo->query("SELECT id, current_price, reserve_price FROM auctions WHERE status = 'active' AND end_time <= NOW()");
        while ($auction = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = ($auction['current_price'] >= $auction['reserve_price']) ? 'sold' : 'ended';
            $winnerStmt = $this->pdo->prepare("SELECT bidder_id FROM bids WHERE auction_id = ? ORDER BY amount DESC LIMIT 1");
            $winnerStmt->execute([$auction['id']]);
            $winnerId = $winnerStmt->fetchColumn();
            $this->pdo->prepare("UPDATE auctions SET status = ?, winner_id = ?, final_price = ? WHERE id = ?")
                      ->execute([$status, $winnerId, $auction['current_price'], $auction['id']]);
            if ($winnerId) {
                // Add notification logic here (e.g., email)
                $this->pdo->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, amount) VALUES (?, ?, (SELECT seller_id FROM auction_items WHERE id = (SELECT item_id FROM auctions WHERE id = ?)), ?)")
                          ->execute([$auction['id'], $winnerId, $auction['id'], $auction['current_price']]);
            }
        }
    }

    public function closeAuction($auctionId) {
        $highestBid = $this->getHighestBid($auctionId);
        if ($highestBid) {
            $stmt = $this->pdo->prepare("UPDATE auctions SET status = 'ended', winner_id = ?, final_price = ? WHERE id = ?");  // Fixed: "UPDATTE" -> "UPDATE"
            $stmt->execute([$highestBid['bidder_id'], $highestBid['amount'], $auctionId]);

            $auction = $this->findById($auctionId);
            $transactionModel = new Transaction();
            $transactionModel->create($auctionId, $highestBid['bidder_id'], $auction['seller_id'], $highestBid['amount']);
            return true;
        }
        return false;
    }

    public function getHighestBid($auctionId) {
        $stmt = $this->pdo->prepare("SELECT bidder_id, amount FROM bids WHERE auction_id = ? ORDER BY amount DESC LIMIT 1");
        $stmt->execute([$auctionId]);
        return $stmt->fetch();
    }
}