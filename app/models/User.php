<?php
defined('APP_PATH') or exit;
class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->one('SELECT * FROM users WHERE email = :v LIMIT 1', $email);
    }

    public function findByUsername(string $username): ?array
    {
        return $this->one('SELECT * FROM users WHERE username = :v LIMIT 1', $username);
    }

    // Login accepts either a username or an email address
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :v OR username = :v LIMIT 1');
        $stmt->execute(['v' => $login]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (full_name, username, email, phone, password, address)
             VALUES (:full_name, :username, :email, :phone, :password, :address)'
        );
        $stmt->execute([
            'full_name' => $d['full_name'],
            'username'  => $d['username'],
            'email'     => $d['email'],
            'phone'     => $d['phone'],
            'password'  => password_hash($d['password'], PASSWORD_DEFAULT),
            'address'   => $d['address'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = :v LIMIT 1');
        $stmt->execute(['v' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function usernameTaken(string $username, int $exceptId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE username = :u AND user_id <> :id LIMIT 1');
        $stmt->execute(['u' => $username, 'id' => $exceptId]);
        return (bool)$stmt->fetchColumn();
    }

    public function updateUsername(int $id, string $username): void
    {
        $stmt = $this->db->prepare('UPDATE users SET username = :u WHERE user_id = :id');
        $stmt->execute(['u' => $username, 'id' => $id]);
    }

    public function updatePassword(int $id, string $password): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password = :p WHERE user_id = :id');
        $stmt->execute(['p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public function updateAddress(int $id, string $address): void
    {
        $stmt = $this->db->prepare('UPDATE users SET address = :a WHERE user_id = :id');
        $stmt->execute(['a' => $address, 'id' => $id]);
    }

    private function one(string $sql, string $value): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['v' => $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
