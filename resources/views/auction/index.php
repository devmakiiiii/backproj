<?php
// Extract variables from controller
$auctions = $auctions ?? [];
$categories = $categories ?? [];
$search = $filters['search'] ?? '';
$category_filter = $filters['category'] ?? '';
?>
<form method="GET">
    <input type="text" name="search" placeholder="Search by title" value="<?php echo htmlspecialchars($search); ?>">
    <select name="category">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php if ($category_filter == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Search">
</form>
<h2>Active Auctions</h2>
<ul>
    <?php foreach ($auctions as $auction): ?>
        <li><a href="/auction/<?php echo $auction['id']; ?>"><?php echo htmlspecialchars($auction['title']); ?> - Current Price: $<?php echo $auction['current_price']; ?> - Ends <?php echo $auction['end_time']; ?> (<?php echo htmlspecialchars($auction['category']); ?>)</a></li>
    <?php endforeach; ?>
</ul>
<a href="/auctions">Back to Dashboard</a>