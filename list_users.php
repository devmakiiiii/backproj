<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();

echo "=== Database Users in auction_db ===\n";
$pdo = new PDO('mysql:host=localhost;dbname=auction_db', 'root', '');
$stmt = $pdo->query('SELECT id, username, email, role FROM users');
echo "Existing users:\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
    echo "  - ID: {$u['id']}, Username: {$u['username']}, Role: {$u['role']}\n";
}