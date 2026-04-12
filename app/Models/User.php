<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class User {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getPdo();
    }

    public function updateRole($userId, $role) {
        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $userId]);
    }

    public function getRole($userId) {
        $stmt = $this->pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function isAdmin($userId) {
        return $this->getRole($userId) === 'admin';
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO users (firstname, middlename, lastname, username, email, mobilenumber, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$data['firstname'], $data['middlename'] ?? null, $data['lastname'], $data['username'], $data['email'], $data['mobilenumber'], password_hash($data['password'], PASSWORD_DEFAULT)]);
        return $this->pdo->lastInsertId();
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticate($username, $password) {
        $stmt = $this->pdo->prepare("SELECT id, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user['id'];
        }
        return false;
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT id, firstname, middlename, lastname, username, email, mobilenumber, role FROM users");
        return $stmt->fetchAll();
    }
}