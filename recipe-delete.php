<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) post('id');

    // Only deletes when the recipe is yours (user_id is in the WHERE)
    $deleted = (new Recipe($pdo))->delete($id, $session->userId());

    $session->flash($deleted ? 'Recipe deleted.' : 'Recipe not found, or you are not allowed to delete it.');
}

header('Location: index.php');
exit;
