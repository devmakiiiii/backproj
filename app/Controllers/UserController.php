<?php
namespace App\Controllers;

use App\Models\User;

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login() {
        session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $userId = $this->userModel->authenticate($username, $password);
            if ($userId) {
                $_SESSION['user_id'] = $userId;
                header('Location: /auctions');
                exit();
            } else {
                $error = 'Invalid credentials.';
            }
        }
        require __DIR__ . '/../../resources/views/user/login.php';
    }

    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            // Basic validation
            if (empty($data['firstname']) || empty($data['lastname']) || empty($data['username']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['password']) < 8 || $data['password'] !== $data['confirmpassword']) {
                $error = 'Invalid input.';
            } else {
                $this->userModel->create($data);
                header('Location: /login');
                exit();
            }
        }
        require __DIR__ . '/../../resources/views/user/signup.php';
    }

    public function logout() {
        session_start();
        session_destroy();
        header('Location: /login');
        exit();
    }
}