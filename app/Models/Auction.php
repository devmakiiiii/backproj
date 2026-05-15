<?php
namespace App\Models;

use App\Core\Database;
use App\Utils\Encryption;
use PDO;
use Exception;
use App\Models\Transaction;

class Auction {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPdo();
    }

    public function getActiveAuctions($filters = []) {
        $query = "SELECT a.id, ai.title, a.current_price, a.end_time, c.name AS category, ai.seller_id AS sellerId, a.status, u.username AS seller, ai.image_url, ai.description FROM auctions a JOIN auction_items ai ON a.item_id = ai.id LEFT JOIN categories c ON ai.category_id = c.id JOIN users u ON ai.seller_id = u.id WHERE a.status = 'active' AND a.end_time > NOW()";
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
        $stmt = $this->pdo->prepare("SELECT a.*, ai.title, ai.description, ai.description_iv, ai.description_tag, ai.image_url, c.name AS category, u.username AS seller, u.id AS seller_id, ai.seller_id AS user_seller_id FROM auctions a JOIN auction_items ai ON a.item_id = ai.id LEFT JOIN categories c ON ai.category_id = c.id JOIN users u ON ai.seller_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $auction = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($auction && $auction['description_iv']) {
            $decrypted = Encryption::decrypt($auction['description'], $auction['description_iv'], $auction['description_tag']);
            $auction['description'] = $decrypted !== false ? $decrypted : $auction['description'];
        }
        unset($auction['description_iv'], $auction['description_tag']);
        return $auction;
    }

    public function getCategories() {
        $stmt = $this->pdo->query("SELECT id, name FROM categories");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function getBids($auctionId) {
        $stmt = $this->pdo->prepare("SELECT b.id, b.amount, b.amount_iv, b.amount_tag, CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')) AS bidderName, b.created_at AS timestamp FROM bids b JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.amount DESC");
        $stmt->execute([$auctionId]);
        $bids = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bids as &$bid) {
            if ($bid['amount_iv']) {
                $decrypted = Encryption::decrypt($bid['amount'], $bid['amount_iv'], $bid['amount_tag']);
                $bid['amount'] = $decrypted !== false ? $decrypted : $bid['amount'];
            }
            unset($bid['amount_iv'], $bid['amount_tag']);
        }
        return $bids;
    }

    public function create($data, $userId, $imageUrls = []) {
        $descEnc = Encryption::encrypt($data['description']);
        $stmt = $this->pdo->prepare("INSERT INTO auction_items (seller_id, category_id, title, description, description_iv, description_tag, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $imageUrl = !empty($imageUrls) ? json_encode($imageUrls) : null;
        $stmt->execute([$userId, $data['category_id'], $data['title'], $descEnc['data'], $descEnc['iv'], $descEnc['tag'], $imageUrl]);
        $itemId = $this->pdo->lastInsertId();

        $stmt2 = $this->pdo->prepare("INSERT INTO auctions (item_id, starting_price, current_price, end_time, bid_increment, start_time, status) VALUES (?, ?, ?, ?, ?, NOW(), 'active')");
        $stmt2->execute([$itemId, $data['starting_price'], $data['starting_price'], $data['end_time'], $data['bid_increment'] ?? 1.00]);
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
        if (!$auction || $auction['status'] !== 'active' || $auction['user_seller_id'] == $userId || $amount < $auction['current_price'] + ($auction['bid_increment'] ?? 1.00)) {
            return false;
        }
        $amountEnc = Encryption::encrypt((string)$amount);
        $stmt = $this->pdo->prepare("INSERT INTO bids (auction_id, bidder_id, amount, amount_iv, amount_tag) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$auctionId, $userId, $amountEnc['data'], $amountEnc['iv'], $amountEnc['tag']]);
        $this->pdo->prepare("UPDATE auctions SET current_price = ? WHERE id = ?")->execute([$amount, $auctionId]);
        return true;
    }

    public function endAuctions() {
        // Refactored from original
        $stmt = $this->pdo->query("SELECT id, current_price, COALESCE(reserve_price, 0) AS reserve_price FROM auctions WHERE status = 'active' AND end_time <= NOW()");
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
            $stmt = $this->pdo->prepare("UPDATE auctions SET status = 'ended', winner_id = ?, final_price = ? WHERE id = ?");
            $stmt->execute([$highestBid['bidder_id'], $highestBid['amount'], $auctionId]);

            $auction = $this->findById($auctionId);
            $transactionModel = new Transaction();
            $transactionModel->create($auctionId, $highestBid['bidder_id'], $auction['user_seller_id'], $highestBid['amount']);
            return true;
        }
        return false;
    }

    public function getHighestBid($auctionId) {
        $stmt = $this->pdo->prepare("SELECT bidder_id, amount, amount_iv, amount_tag FROM bids WHERE auction_id = ? ORDER BY amount DESC LIMIT 1");
        $stmt->execute([$auctionId]);
        $bid = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($bid && $bid['amount_iv']) {
            $decrypted = Encryption::decrypt($bid['amount'], $bid['amount_iv'], $bid['amount_tag']);
            $bid['amount'] = $decrypted !== false ? $decrypted : $bid['amount'];
        }
        unset($bid['amount_iv'], $bid['amount_tag']);
        return $bid;
    }
}