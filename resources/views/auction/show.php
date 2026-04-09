<h2><?php echo htmlspecialchars($auction['title']); ?></h2>
<p>Description: <?php echo htmlspecialchars($auction['description']); ?></p>
<p>Current Price: $<?php echo $auction['current_price']; ?></p>
<form method="POST">
    <input type="number" name="bid_amount" required>
    <button type="submit" name="bid">Bid</button>
</form>
<h3>Bids</h3>
<ul>
    <?php foreach ($bids as $bid): ?>
        <li>$<?php echo $bid['amount']; ?> by <?php echo $bid['username']; ?></li>
    <?php endforeach; ?>
</ul>
<a href="/auctions">Back</a>