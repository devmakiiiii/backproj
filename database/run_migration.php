<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$config = require __DIR__ . '/../config/database.php';
$pdo = new PDO("mysql:host={$config['host']};charset=utf8", $config['user'], $config['pass']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Drop database if exists to start fresh
$pdo->exec("DROP DATABASE IF EXISTS {$config['name']}");
$pdo->exec("CREATE DATABASE {$config['name']}");
$pdo->exec("USE {$config['name']}");

$sql = file_get_contents(__DIR__ . '/migrations/001_create-schema.sql');
// Remove the first two lines as we handled CREATE and USE
$lines = explode("\n", $sql);
array_shift($lines); // remove CREATE DATABASE
array_shift($lines); // remove USE
$sql = implode("\n", $lines);

// Remove comment lines
$lines = explode("\n", $sql);
$lines = array_filter($lines, function($line) {
    return !preg_match('/^\s*--/', trim($line));
});
$sql = implode("\n", $lines);

$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/CREATE INDEX/', $statement) && !preg_match('/ALTER TABLE/', $statement)) {
        try {
            $pdo->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (Exception $e) {
            echo "Skipped: " . substr($statement, 0, 50) . " - " . $e->getMessage() . "\n";
        }
    }
}

echo "Migration completed.\n";
?>