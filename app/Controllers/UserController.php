<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;

class UserController {
    private $userModel;
    private $dbError;

    public function __construct() {
        try {
            $this->userModel = new User();
        } catch (Exception $e) {
            // Store the error to handle in methods
            $this->dbError = $e->getMessage();
        }
    }

    public function login() {
        header('Content-Type: application/json');
        try {
            if ($this->dbError) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $this->dbError]);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['username']) || !isset($input['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            $userId = $this->userModel->authenticate($input['username'], $input['password']);
            if ($userId) {
                $role = $this->userModel->getRole($userId);
                $payload = [
                    'user_id' => $userId,
                    'role' => $role,
                    'exp' => time() + 3600 // 1 hour expiry
                ];
                $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
                echo json_encode(['token' => $jwt]);
            } else {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
        }
    }

    public function signup() {
        header('Content-Type: application/json');
        if ($this->dbError) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $this->dbError]);
            return;
        }
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
        if ($input['password'] !== $input['confirmpassword']) {
            http_response_code(400);
            echo json_encode(['error' => 'Passwords do not match']);
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
            echo json_encode(['error' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    public function logout() {
        header('Content-Type: application/json');
        // For JWT, logout is typically handled on frontend by removing token
        // If needed, implement token blacklisting here
        echo json_encode(['message' => 'Logged out']);
    }

    public function getUserById($id) {
        if ($this->dbError) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error: ' . $this->dbError]);
            return ['error' => 'Database error: ' . $this->dbError];
        }
        $user = $this->userModel->findById($id);
        if ($user) {
            return $user;
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return ['error' => 'User not found'];
        }
    }
}