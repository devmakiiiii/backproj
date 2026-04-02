<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h2>Welcome, <?php echo $_SESSION['username']; ?>!</h2>
<p>This is your dashboard. You can manage your auctions here.</p>

<a href="logout.php">Logout</a>

