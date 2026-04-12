<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['username']) || !isset($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }
        $userId = $this->userModel->authenticate($input['username'], $input['password']);
        if ($userId) {
            $payload = [
                'user_id' => $userId,
                'exp' => time() + 3600 // 1 hour expiry
            ];
            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], ['HS256']);
            echo json_encode(['token' => $jwt]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    public function signup() {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            return;
        }
        // Validation
        if (empty($input['firstname']) || empty($input['lastname']) || empty($input['username']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL) || strlen($input['password']) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $input['password']) || $input['password'] !== $input['confirmpassword']) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data: password must be at least 8 characters with uppercase, lowercase, number, and special character']);
            return;
        }
        if ($this->userModel->findByUsername($input['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
            return;
        }
        if ($this->userModel->findByEmail($input['email'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email already exists']);
            return;
        }
        try {
            $this->userModel->create($input);
            http_response_code(201);
            echo json_encode(['message' => 'User created successfully']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create user']);
        }
    }

    public function logout() {
        header('Content-Type: application/json');
        // For JWT, logout is typically handled on frontend by removing token
        // If needed, implement token blacklisting here
        echo json_encode(['message' => 'Logged out']);
    }
}