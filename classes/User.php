<?php
declare(strict_types=1);

class User extends Repository
{
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() !== false;
    }

    // Saves a new member. The password is stored only as a hash.
    public function create(string $name, string $email, string $password): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password) VALUES (:name, :email, :password)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    // Returns ['id' => ..., 'name' => ...] when the email + password are right, otherwise null.
    public function verifyLogin(string $email, string $password): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, password FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user === false || !password_verify($password, $user['password'])) {
            return null;
        }

        return ['id' => $user['id'], 'name' => $user['name']];
    }
}
