<?php
declare(strict_types=1);
require 'init.php';

// Called by JavaScript fetch() so favorites change WITHOUT reloading the page.
// It answers with JSON instead of HTML.
header('Content-Type: application/json');

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if (!$session->isLoggedIn()) {
    respond(401, ['ok' => false, 'error' => 'Please log in.']);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'POST only.']);
}

$recipeId = (int) post('recipe_id');
$action = post('action');

if ($recipeId <= 0 || !in_array($action, ['add', 'remove'], true)) {
    respond(422, ['ok' => false, 'error' => 'Invalid request.']);
}

$favorites = new Favorite($pdo);

if ($action === 'add') {
    if (!(new Recipe($pdo))->exists($recipeId)) {
        respond(404, ['ok' => false, 'error' => 'Recipe not found.']);
    }
    $favorites->add($session->userId(), $recipeId);
} else {
    $favorites->remove($session->userId(), $recipeId);
}

respond(200, ['ok' => true, 'favorited' => $action === 'add']);
