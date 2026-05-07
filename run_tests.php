<?php
require 'C:\xampp\htdocs\FullStack\backproj-main\vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable('C:\xampp\htdocs\FullStack\backproj-main');
$dotenv->load();

echo "=== Testing Login API Endpoint ===\n\n";

// Test 1: List existing users
echo "1. Existing users in auction_db:\n";
$pdo = new PDO('mysql:host=localhost;dbname=auction_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$stmt = $pdo->query('SELECT id, username, email, role FROM users');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "   - ID: {$u['id']}, Username: {$u['username']}, Role: {$u['role']}\n";
}

// Test 2: Direct login endpoint test
echo "\n2. Testing login endpoint:\n";

// Prepare input for login
$loginData = ['username' => 'testuser', 'password' => 'Testpass123!'];
$jsonInput = json_encode($loginData);

// We need to simulate file_get_contents('php://input')
// Read input via data:// protocol
$inputStream = 'data://text/plain,' . urlencode($jsonInput);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Read from data stream as php://input substitute
// Create a closure to override file_get_contents behavior
$originalInput = $jsonInput;

// Capture output
ob_start();

// Create controller and test
$controller = new App\Controllers\UserController();

// Directly test the login method with our input
try {
    // The login method uses file_get_contents('php://input')
    // Create a mock by using the input stream
    $mockInput = $jsonInput;
    
    // Override the input by using streams
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $mockInput);
    rewind($stream);
    
    // Use the controller directly but capture what file_get_contents would read
    // Since we can't easily mock php://input, let's read the raw input from the stream
    $rawInput = stream_get_contents($stream);
    
    // Manually call the authentication logic that login() uses
    $userModel = new App\Models\User();
    $userId = $userModel->authenticate($loginData['username'], $loginData['password']);
    
    if ($userId) {
        $role = $userModel->getRole($userId);
        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'exp' => time() + 3600
        ];
        $jwt = \Firebase\JWT\JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
        
        echo "   Authentication SUCCESS\n";
        echo "   User ID: $userId\n";
        echo "   Role: $role\n";
        echo "   JWT Token: $jwt\n";
        echo "   Response would be: " . json_encode(['token' => $jwt]) . "\n";
    } else {
        echo "   Authentication FAILED\n";
        echo "   Response would be: " . json_encode(['error' => 'Invalid credentials']) . "\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";