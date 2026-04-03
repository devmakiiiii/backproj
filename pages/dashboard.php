<?php
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
<p>This is your dashboard. You can manage your auctions here.</p>
<ul>
    <li><a href="create_auction.php">Create New Auction</a></li>
    <li><a href="view_auctions.php">View Active Auctions</a></li>
    <li><a href="my_auctions.php">My Auctions</a><li>
</ul>
<a href="logout.php">Logout</a>

