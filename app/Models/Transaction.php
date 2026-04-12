<?php
namespace App\Models;

class Transaction {
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS']);
    }

    public function create($auctionId, $buyerId, $sellerId, $amount) {
        $stmt = $this->pdo->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, amount) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$auctionId, $buyerId, $sellerId, $amount]);
    }

    public function updatePaymentStatus($transactionId, $status, $method = null) {
        $stmt = $this->pdo->prepare("UPDATE transactions SET payment_status = ?, payment_method = ? WHERE id = ?");
        return $stmt->execute([$status, $method, $transactionId]);
    }

    public function findByAuctionId($auctionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM transactions WHERE auction_id = ?");
        $stmt->execute([$auctionId]);
        return $stmt->fetch();
    }
}