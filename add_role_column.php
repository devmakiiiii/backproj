<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();
$pdo = new PDO('mysql:host=localhost;dbname=auction_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->prepare('ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT "user" AFTER password');
$stmt->execute();
echo 'Role column added successfully';
?>