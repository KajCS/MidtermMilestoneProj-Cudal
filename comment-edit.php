<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

$comments = new Comment($pdo);
$validator = new Validator();

$id = (int) ($_GET['id'] ?? 0);
$comment = $comments->findOwned($id, $session->userId());

if ($comment === null) {
    http_response_code(404);
    exit('Comment not found, or you are not allowed to edit it.');
}

$errors = [];
$form = ['body' => $comment['body'], 'rating' => (string) $comment['rating']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = ['body' => post('body'), 'rating' => post('rating')];
    $errors = $validator->validateComment($form);

    if (!$errors) {
        $comments->update($id, $session->userId(), $form['body'], (int) $form['rating']);
        $session->flash('Comment updated.');
        header('Location: recipe.php?id=' . $comment['recipe_id'] . '#comment-' . $id);
        exit;
    }
}

$ratingLabels = [5 => 'Masarap!', 4 => 'Very good', 3 => 'Good', 2 => 'Okay', 1 => 'Needs work'];

$title = 'Edit comment';
require 'partials/header.php';
?>

<section class="form-page">
    <h1>Edit comment</h1>
    <?php require 'partials/errors.php'; ?>

    <form method="post" class="card">
        <label>Your rating
            <select name="rating" required>
                <option value="">Choose a rating…</option>
                <?php foreach ($ratingLabels as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $form['rating'] === (string) $value ? 'selected' : '' ?>>
                        <?= stars((float) $value) ?> <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Comment
            <textarea name="body" rows="4" minlength="2" maxlength="1000" required><?= e($form['body']) ?></textarea>
        </label>

        <div class="form-actions">
            <button type="submit" class="button">Save changes</button>
            <a class="button outline" href="recipe.php?id=<?= e($comment['recipe_id']) ?>#comments">Cancel</a>
        </div>
    </form>
</section>

<?php require 'partials/footer.php'; ?>
