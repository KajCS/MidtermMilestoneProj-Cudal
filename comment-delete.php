<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comments = new Comment($pdo);
    $id = (int) post('id');

    // Load it first (only if yours) so we know which recipe to go back to
    $comment = $comments->findOwned($id, $session->userId());

    if ($comment !== null) {
        $comments->delete($id, $session->userId());
        $session->flash('Comment deleted.');
        header('Location: recipe.php?id=' . $comment['recipe_id'] . '#comments');
        exit;
    }

    $session->flash('Comment not found, or you are not allowed to delete it.');
}

header('Location: index.php');
exit;
