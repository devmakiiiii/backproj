<?php
namespace App\Models;

use App\Core\Database;
use App\Utils\Encryption;
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

    public function getByItemId($itemId) {
        $stmt = $this->pdo->prepare("SELECT b.id, b.amount, b.amount_iv, b.amount_tag, CONCAT(COALESCE(u.firstname, ''), ' ', COALESCE(u.lastname, '')) AS bidderName, b.created_at AS timestamp FROM bids b JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.amount DESC");
        $stmt->execute([$itemId]);
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

    public function placeBid($auctionId, $userId, $amount, $auctionModel) {
        $auction = $auctionModel->findById($auctionId);
        if (!$auction || $auction['status'] !== 'active' || $auction['user_seller_id'] == $userId || $amount <= $auction['current_price'] + ($auction['bid_increment'] ?? 1.00)) {
            return false;
        }
        $amountEnc = Encryption::encrypt((string)$amount);
        $stmt = $this->pdo->prepare("INSERT INTO bids (auction_id, bidder_id, amount, amount_iv, amount_tag) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$auctionId, $userId, $amountEnc['data'], $amountEnc['iv'], $amountEnc['tag']]);
        $this->pdo->prepare("UPDATE auctions SET current_price = ? WHERE id = ?")->execute([$amount, $auctionId]);
        return true;
    }
}