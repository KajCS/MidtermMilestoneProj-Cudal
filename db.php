<?php
declare(strict_types=1);

// The ONE file that holds the database settings and the PDO connection.
// Every page gets it through init.php (require).
$host = '127.0.0.1';
$dbname = 'recipe_site';
$user = 'root';
$pass = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,      // errors throw, so rollBack() can run
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // rows come back as ['title' => ...]
        PDO::ATTR_EMULATE_PREPARES => false,              // real prepared statements
    ]
);
