<?php

class User {
    private $pdo;
    private $saltLength = 16; 

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function generateSalt() {
        return bin2hex(random_bytes($this->saltLength));
    }


    public function register($username, $password, $totpSecret) {

        $salt = $this->generateSalt();
        $hashedPassword = password_hash($password . $salt, PASSWORD_BCRYPT);

        try {
            $stmt = $this->pdo->prepare('INSERT INTO users (username, hashed_password, salt, totp_secret) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, $hashedPassword, $salt, $totpSecret]);
            return true;

        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                error_log('Duplicate entry error: ' . $e->getMessage());
                return 'Username already exists.';

            } else {
                error_log('SQL Error: ' . $e->getMessage());
                return 'Error registering user.';
            }
        }
    }

    public function getUser($username) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log('SQL Error: ' . $e->getMessage());
            return 'SQL Error: ' . $e->getMessage();
        }
    }


}
