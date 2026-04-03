<?php
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auctions = $conn->prepare("
    SELECT a.id, ai.title, a.status, a.current_price, a.end_time
    FROM auctions a
    JOIN auction_items ai ON a.item_id = ai.id
    WHERE ai.seller_id = ?
");
$auctions->bind_param("i", $_SESSION['user_id']);
$auctions->execute();
$auctions = $auctions->get_result();
?>

<h2>My Auctions</h2>
<ul>
    <?php while ($auction = $auctions->fetch_assoc()): ?>
        <li><?php echo $auction['title']; ?> - Status: <?php echo $auction['status']; ?> - Price: $<?php echo $auction['current_price']; ?> - Ends: <?php echo $auction['end_time']; ?></li>
    <?php endwhile; ?>
</ul>
<a href="dashboard.php">Back to Dashboard</a>