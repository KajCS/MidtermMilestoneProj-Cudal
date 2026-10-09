<?php
declare(strict_types=1);

// Loaded at the top of every page: database, classes, session, small helpers.
require __DIR__ . '/db.php';

require __DIR__ . '/classes/SessionManager.php';
require __DIR__ . '/classes/Validator.php';
require __DIR__ . '/classes/Repository.php';
require __DIR__ . '/classes/User.php';
require __DIR__ . '/classes/Category.php';
require __DIR__ . '/classes/Recipe.php';
require __DIR__ . '/classes/Comment.php';
require __DIR__ . '/classes/Favorite.php';

$session = new SessionManager();
$session->start();

// Print anything a user typed safely (stops XSS).
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Read one text field from a submitted form. Returns '' if missing or not text.
function post(string $key, bool $trim = true): string
{
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    return $trim ? trim($value) : $value;
}

// 4 -> "★★★★☆"
function stars(float $rating): string
{
    $full = (int) round($rating);
    return str_repeat('★', $full) . str_repeat('☆', 5 - $full);
}

// "2026-10-09 13:35:00" -> "Oct 9, 2026 · 1:35 PM"
function nice_date(string $datetime): string
{
    return date('M j, Y · g:i A', (int) strtotime($datetime));
}
