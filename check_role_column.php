<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();
$pdo = new PDO('mysql:host=localhost;dbname=auction_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Users table columns:\n";
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
}

// Check specifically for role column
$hasRole = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'role') {
        $hasRole = true;
        break;
    }
}

if (!$hasRole) {
    echo "\nERROR: 'role' column not found in users table!\n";
} else {
    echo "\nRole column found.\n";
}
?>