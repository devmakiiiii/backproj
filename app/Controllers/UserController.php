<?php
namespace App\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use App\Services\AuthService;

class UserController {
    private $userModel;
    private $dbError;

    public function __construct() {
        try {
            $this->userModel = new User();
        } catch (Exception $e) {
            $this->dbError = $e->getMessage();
        }
    }

    public function register() {
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
        if (empty($input['firstname']) || empty($input['lastname']) || empty($input['username']) || !filter_var($input['email'], FILTER_VALIDATE_EMAIL) || strlen($input['password']) < 8 || $input['password'] !== $input['confirmpassword']) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input data']);
            return;
        }
        if ($this->userModel->findByUsername($input['username'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Username already exists']);
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
                    'exp' => time() + 3600
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

    public function getProfile() {
        header('Content-Type: application/json');
        try {
            $user = AuthService::verifyToken();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            $userData = $this->userModel->findById($user->user_id);
            if ($userData) {
                echo json_encode($userData);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'User not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function getUserById($id) {
        header('Content-Type: application/json');
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

    public function updateProfile() {
        header('Content-Type: application/json');
        try {
            $user = AuthService::verifyToken();
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            if ($this->userModel->updateProfile($user->user_id, $input)) {
                $updated = $this->userModel->findById($user->user_id);
                echo json_encode($updated);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to update profile']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
        }
    }
}