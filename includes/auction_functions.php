<?php
include "config.php";

function endAuctions() {
    global $conn;
    $ended = $conn->query("SELECT id, current_price, reserve_price FROM auctions WHERE status = 'active' AND end_time <= NOW()");
    while ($auction = $ended->fetch_assoc()) {
        $status = ($auction['current_price'] >= $auction['reserve_price']) ? 'sold' : 'ended';
        // Find winner (highest bid)
        $winner = $conn->prepare("SELECT bidder_id FROM bids WHERE auction_id = ? ORDER BY amount DESC LIMIT 1");
        $winner->bind_param("i", $auction['id']);
        $winner->execute();
        $winner_id = $winner->get_result()->fetch_assoc()['bidder_id'] ?? null;

        $conn->prepare("UPDATE auctions SET status = ?, winner_id = ?, final_price = ? WHERE id = ?")
              ->execute([$status, $winner_id, $auction['current_price'], $auction['id']]);

        if ($winner_id) {
            // Insert transaction
            $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, amount) VALUES (?, ?, (SELECT seller_id FROM auction_items WHERE id = (SELECT item_id FROM auctions WHERE id = ?)), ?)")
                  ->execute([$auction['id'], $winner_id, $auction['id'], $auction['current_price']]);
        }
    }
}
?>