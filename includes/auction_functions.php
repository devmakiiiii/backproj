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
            notifyWinner($auction['id'], $winner_id, $auction['current_price']);
            // Insert transaction
            $conn->prepare("INSERT INTO transactions (auction_id, buyer_id, seller_id, amount) VALUES (?, ?, (SELECT seller_id FROM auction_items WHERE id = (SELECT item_id FROM auctions WHERE id = ?)), ?)")
                  ->execute([$auction['id'], $winner_id, $auction['id'], $auction['current_price']]);
        }
    }
}

function notifyWinner($auction_id, $winner_id, $final_price) {
    global $conn;
    $auction = $conn->query("SELECT ai.title FROM auctions a JOIN auction_items ai ON a.item_id = ai.id WHERE a.id = $auction_id")->fetch_assoc();
    $winner = $conn->query("SELECT email FROM users WHERE id = $winner_id")->fetch_assoc();
    $subject = "You won the auction for " .  $auction['title'];
    $message = "Congratulations! You won with a bid of $" . $final_price . ".";
    mail($winner['email'], $subject, $message);
}
?>