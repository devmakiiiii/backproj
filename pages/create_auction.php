<?php
include "../includes/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['create'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $starting_price = $_POST['starting_price'];
    $reserve_price = $_POST['reserve_price'];
    $category_id = $_POST['category_id'];
    $end_time = $_POST['end_time'];  // Format: YYYY-MM-DD HH:MM:SS

    // Insert item
    $stmt = $conn->prepare("INSERT INTO auction_items (seller_id, category_id, title, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiss", $_SESSION['user_id'], $category_id, $title, $description);
    $stmt->execute();
    $item_id = $stmt->insert_id;

    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_url = $target_file;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO auction_items (seller_id, category_id, title, description, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $_SESSION['user_id'], $category_id, $title, $description, $image_url);

    // Insert auction
    $stmt2 = $conn->prepare("INSERT INTO auctions (item_id, starting_price, reserve_price, current_price, start_time, end_time, status) VALUES (?, ?, ?, ?, NOW(), ?, 'active')");
    $stmt2->bind_param("iddss", $item_id, $starting_price, $reserve_price, $starting_price, $end_time);
    $stmt2->execute();

    header("Location: my_auctions.php");
    exit();
}

// Fetch categories for dropdown
$categories = $conn->query("SELECT id, name FROM categories");
?>

<form method="POST">
    <h2>Create Auction</h2>
    <input type="text" name="title" placeholder="Item Title" required><br>
    <textarea name="description" placeholder="Description" required></textarea><br>
    <input type="file" name="image" accept="image/*"><br>
    <input type="number" step="0.01" name="starting_price" placeholder="Starting Price" required><br>
    <input type="number" step="0.01" name="reserve_price" placeholder="Reserve Price"><br>
    <select name="category_id" required>
        <?php while ($cat = $categories->fetch_assoc()): ?>
            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
        <?php endwhile; ?>
    </select><br>
    <input type="datetime-local" name="end_time" required><br>
    <input type="submit" name="create" value="Create Auction">
</form>
<a href="dashboard.php">Back to Dashboard</a>