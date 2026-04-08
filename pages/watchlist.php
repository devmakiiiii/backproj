<?php 
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$watchlist = $conn->prepare("
    SELECT a.id, ai.title, a.current_price, a.end_time, c.name AS category
    FROM watchlist w
    JOIN auctions a ON w.auction_id = a.id
    JOIN auction_items ai ON a.item_id = ai.id
    LEFT JOIN categories c ON ai.category_id = c.id
    WHERE w.user_id = ? AND a.status = 'active' AND a.end_time > NOW()
");
$watchlist->bind_param("i", $_SESSION['user_id']);
$watchlist->execute();
$watchlist = $watchlist->get_result();
?>

<h2>My Watchlist</h2>
<ul>
    <?php while ($auction = $watchlist->fetch_assoc()): ?>
        <li>
            <a href="auction_detail.php?id=<?php echo $auction['id']; ?>">
                <?php echo $auction['title']; ?> - Current Price: $<?php echo $auction['current_price']; ?> - Ends <?php echo $auction['end_time']; ?> (<?php echo $auction['category']; ?>)
            </a>
        </li>
    <?php endwhile; ?>
</ul>
<a href="dashboard.php">Back to Dashboard</a>