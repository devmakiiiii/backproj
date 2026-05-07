<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();
$pdo = new PDO('mysql:host=localhost;dbname=auction_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
?>