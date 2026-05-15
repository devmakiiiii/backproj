<?php
namespace App\Models;

use App\Core\Database;
use App\Utils\Encryption;
use PDO;

class User {
    private $pdo;

    public function __construct() {
        try {
            $this->pdo = Database::getInstance()->getPdo();
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $user['email'] = Encryption::decrypt($user['email'], $user['email_iv'] ?? null, $user['email_tag'] ?? null);
            $user['mobilenumber'] = Encryption::decrypt($user['mobilenumber'], $user['phone_iv'] ?? null, $user['phone_tag'] ?? null);
            unset($user['email_iv'], $user['email_tag'], $user['phone_iv'], $user['phone_tag']);
        }
        return $user;
    }

    public function create($data) {
        $emailEnc = Encryption::encrypt($data['email']);
        $phoneEnc = Encryption::encrypt($data['mobilenumber']);
        $stmt = $this->pdo->prepare("INSERT INTO users (firstname, middlename, lastname, username, email, email_iv, email_tag, mobilenumber, phone_iv, phone_tag, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['firstname'], 
            $data['middlename'] ?? null, 
            $data['lastname'], 
            $data['username'], 
            $emailEnc['data'], 
            $emailEnc['iv'], 
            $emailEnc['tag'], 
            $phoneEnc['data'], 
            $phoneEnc['iv'], 
            $phoneEnc['tag'], 
            password_hash($data['password'], PASSWORD_DEFAULT)
        ]);
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
        $stmt = $this->pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            return $user['id'];
        }
        return false;
    }

    public function updateProfile($userId, $data) {
        $emailEnc = Encryption::encrypt($data['email']);
        $phoneEnc = Encryption::encrypt($data['mobilenumber']);
        $stmt = $this->pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, email_iv = ?, email_tag = ?, mobilenumber = ?, phone_iv = ?, phone_tag = ? WHERE id = ?");
        return $stmt->execute([
            $data['firstname'], 
            $data['lastname'], 
            $emailEnc['data'], 
            $emailEnc['iv'], 
            $emailEnc['tag'], 
            $phoneEnc['data'], 
            $phoneEnc['iv'], 
            $phoneEnc['tag'],
            $userId
        ]);
    }

    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT id, firstname, middlename, lastname, username, email, email_iv, email_tag, mobilenumber, phone_iv, phone_tag, role FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as &$u) {
            if ($u['email_iv']) {
                $u['email'] = Encryption::decrypt($u['email'], $u['email_iv'], $u['email_tag']);
            }
            if ($u['phone_iv']) {
                $u['mobilenumber'] = Encryption::decrypt($u['mobilenumber'], $u['phone_iv'], $u['phone_tag']);
            }
            unset($u['email_iv'], $u['email_tag'], $u['phone_iv'], $u['phone_tag']);
        }
        return $users;
    }
}