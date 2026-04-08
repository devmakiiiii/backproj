<?php
include "../includes/config.php";

$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "
    SELECT a.id, ai.title, a.current_price, a.end_time, c.name AS category
    FROM auctions a
    JOIN auction_items ai ON a.item_id = ai.id
    LEFT JOIN categories c ON ai.category_id = c.id
    WHERE a.status = 'active' AND a.end_time > NOW()
";

$params = [];
$types = '';

if ($search) {
    $query .= " AND ai.title LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

if ($category_filter) {
    $query .= " AND ai.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$query .= " ORDER BY a.end_time ASC";

$stmt = $conn->prepare($query);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$auctions = $stmt->get_result();

$categories = $conn->query("SELECT id, name FROM categories");
?>

<form method="GET">
    <input type="text" name="search" placeholder="Search by title" value="<?php echo htmlspecialchars($search); ?>">
    <select name="category">
        <option value="">All Categories</option>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>" <?php if ($category_filter == $cat['id']) echo 'selected'; ?>><?php echo $cat['name']; ?></option>
        <?php endwhile; ?>
    </select>
    <input type="submit" value="Search">
</form>

if (!isset($_SESSION['user_id'])) {
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