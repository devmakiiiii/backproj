<?php
include "../includes/config.php";

if (isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$auctions = $conn->query("
    SELECT a.id, ai.title, a.current_price, a.end_time, c.name AS category
    FROM auctions a 
    JOIN auction_items ai ON a.item_id = ai.id
    LEFT JOIN categories c ON ai.category_id = c.id
    WHERE a.status = 'active' AND a.end_time > NOW()
    ORDER BY a.end_time ASC
");
?>

<h2>Active Auctions</h2>
<ul>
    <?php while ($auction = $auctions->fetch_assoc()): ?>
        <li>
            <a href="auction_detail.php?id=<?php echo $auction['id']; ?>">
                <?php echo $auction['title']; ?> - Current Price: $<?php echo $auction['current_price']; ?> - Ends <?php echo $auction['end_time']; ?> (<?php echo $auction['category']; ?>)
            </a>
        </li>
    <?php endwhile; ?>
</ul>
<a href="dashboard.php">Back to Dashboard</a>