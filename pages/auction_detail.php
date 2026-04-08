<?php
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auction_id = $_GET['id'];
$auction = $conn->prepare("
    SELECT a.*, ai.title, ai.description, ai.image_url, u.username AS seller
    FROM auctions a
    JOIN auction_items ai ON a.item_id = ai.id
    JOIN users u ON ai.seller_id = u.id
    WHERE a.id = ?
");
$auction->bind_param("i", $auction_id);
$auction->execute();
$auction = $auction->get_result()->fetch_assoc();

if (!$auction) {
    echo "Auction not found.";
    exit();
}

// Fetch bids
$bids = $conn->prepare("SELECT b.amount, u.username, b.created_at FROM bids b JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.amount DESC");
$bids->bind_param("i", $auction_id);
$bids->execute();
$bids = $bids->get_result();

if (isset($_POST['bid'])) {
    $bid_amount = $_POST['bid_amount'];
    $min_bid = $auction['current_price'] + $auction['bid_increment'];
    if ($bid_amount >= $min_bid) {
        $stmt = $conn->prepare("INSERT INTO bids (auction_id, bidder_id, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $auction_id, $_SESSION['user_id'], $bid_amount);
        $stmt->execute();
        $conn->query("UPDATE auctions SET current_price = $bid_amount WHERE id = $auction_id");
        header("Location: auction_detail.php?id=$auction_id");
        exit();
    } else {
        echo "Bid must be at least $" . $min_bid . ".";
    }
}

// Check if auction is in user's watchlist
$watchlist_check = $conn->prepare("SELECT id FROM watchlist WHERE user_id = ? AND auction_id = ?");
$watchlist_check->bind_param("ii", $_SESSION['user_id'], $auction_id);
$watchlist_check->execute();
$is_watched = $watchlist_check->get_result()->num_rows > 0;

if (isset($_POST['add_watchlist'])) {
    $stmt = $conn->prepare("INSERT IGNORE INTO watchlist (user_id, auction_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $_SESSION['user_id'], $auction_id);
    $stmt->execute();
    header("Location: auction_detail.php?id=$auction_id");
    exit();
}

if (isset($_POST['remove_watchlist'])) {
    $stmt = $conn->prepare("DELETE FROM watchlist WHERE user_id = ? AND auction_id = ?");
    $stmt->bind_param("ii", $_SESSION['user_id'], $auction_id);
    $stmt->execute();
    header("Location: auction_detail.php?id=$auction_id");
    exit();
}
?>

<h2><?php echo $auction['title']; ?></h2>
<?php if ($auction['image_url']): ?>
    <img src="<?php echo $auction['image_url']; ?>" alt="Item Image" style="max-width: 300px;">
<?php endif; ?>
<p>Description: <?php echo $auction['description']; ?></p>
<p>Seller: <?php echo $auction['seller']; ?></p>
<p>Current Price: $<?php echo $auction['current_price']; ?></p>
<p>Reserve Price: $<?php echo $auction['reserve_price']; ?> (hidden if not met)</p>
<p>Ends: <?php echo $auction['end_time']; ?></p>

<?php if (strtotime($auction['end_time']) > time()): ?>
    <form method="POST">
        <input type="number" step="0.01" name="bid_amount" placeholder="Your Bid" required>
        <input type="submit" name="bid" value="Place Bid">
    </form>
<?php else: ?>
    <p>Auction ended.</p>
<?php endif; ?>

<?php if (!$is_watched): ?>
    <form method="POST">
        <input type="submit" name="add_watchlist" value="Add to Watchlist">
    </form>
<?php else: ?>
    <form method="POST">
        <input type="submit" name="remove_watchlist" value="Remove from Watchlist">
    </form>
<?php endif; ?>

<h3>Bid History</h3>
<ul>
    <?php while ($bid = $bids->fetch_assoc()): ?>
        <li>$<?php echo $bid['amount']; ?> by <?php echo $bid['username']; ?> at <?php echo $bid['created_at']; ?></li>
    <?php endwhile; ?>
</ul>
<a href="view_auctions.php">Back to Auctions</a>