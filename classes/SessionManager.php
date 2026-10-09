<?php
declare(strict_types=1);

// Owns everything about $_SESSION (Week 6, slide 14).
class SessionManager
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Safer session cookie (Week 5, slide 15)
            session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
            session_start();
        }
    }

    public function login(array $user): void
    {
        session_regenerate_id(true); // new session id after login
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public function userName(): string
    {
        return (string) ($_SESSION['user_name'] ?? '');
    }

    // Member pages: guests are sent to the login page.
    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    // Login and register: members are sent to the recipe feed.
    public function requireGuest(): void
    {
        if ($this->isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
    }

    // A one-time message shown on the next page ("Recipe saved!").
    public function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }

    public function takeFlash(): ?string
    {
        $message = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $message;
    }
}
