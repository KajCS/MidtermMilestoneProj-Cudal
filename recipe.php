<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

$recipes = new Recipe($pdo);
$comments = new Comment($pdo);
$validator = new Validator();

$id = (int) ($_GET['id'] ?? 0);
$recipe = $recipes->find($id, $session->userId());

if ($recipe === null) {
    http_response_code(404);
    exit('Recipe not found.');
}

$errors = [];
$commentForm = ['body' => '', 'rating' => ''];

// Add a comment + rating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentForm = ['body' => post('body'), 'rating' => post('rating')];
    $errors = $validator->validateComment($commentForm);

    if (!$errors) {
        $comments->create($id, $session->userId(), $commentForm['body'], (int) $commentForm['rating']);
        $session->flash('Salamat! Your comment was posted.');
        header('Location: recipe.php?id=' . $id . '#comments');
        exit;
    }
}

$ingredients = $recipes->ingredients($id);
$commentList = $comments->forRecipe($id);
$isOwner = (int) $recipe['user_id'] === $session->userId();

// One step per line -> numbered list
$steps = array_values(array_filter(
    array_map('trim', preg_split('/\R/', $recipe['steps'])),
    fn (string $line): bool => $line !== ''
));

$ratingLabels = [5 => 'Masarap!', 4 => 'Very good', 3 => 'Good', 2 => 'Okay', 1 => 'Needs work'];

$title = $recipe['title'];
require 'partials/header.php';
?>

<article class="recipe-detail">
    <header class="recipe-hero">
        <p><span class="tag"><?= e($recipe['category']) ?></span></p>
        <h1><?= e($recipe['title']) ?></h1>
        <p class="lead"><?= e($recipe['description']) ?></p>

        <p class="meta">
            By <?= e($recipe['author']) ?> · <?= e(nice_date($recipe['created_at'])) ?>
            <?php if ($recipe['updated_at'] !== null): ?>
                <span class="tag tag-edited" title="Edited <?= e(nice_date($recipe['updated_at'])) ?>">Edited</span>
            <?php endif; ?>
        </p>

        <p class="rating-line">
            <?php if ($recipe['avg_rating'] !== null): ?>
                <span class="stars"><?= stars((float) $recipe['avg_rating']) ?></span>
                <?= e($recipe['avg_rating']) ?> average from <?= e($recipe['comment_count']) ?> rating<?= (int) $recipe['comment_count'] === 1 ? '' : 's' ?>
            <?php else: ?>
                No ratings yet
            <?php endif; ?>
        </p>

        <div class="actions">
            <button type="button" class="fav-btn" data-recipe-id="<?= e($recipe['id']) ?>"
                    aria-pressed="<?= $recipe['is_favorite'] ? 'true' : 'false' ?>">
                <span class="fav-icon" aria-hidden="true">♥</span>
                <span class="fav-label"><?= $recipe['is_favorite'] ? 'Saved' : 'Save' ?></span>
            </button>

            <?php if ($isOwner): ?>
                <a class="button outline small" href="recipe-form.php?id=<?= e($recipe['id']) ?>">Edit recipe</a>
                <form method="post" action="recipe-delete.php" class="inline"
                      data-confirm="Delete this recipe? Its ingredients, comments and saves will be removed too.">
                    <input type="hidden" name="id" value="<?= e($recipe['id']) ?>">
                    <button type="submit" class="button danger small">Delete</button>
                </form>
            <?php endif; ?>
        </div>
    </header>

    <div class="recipe-body">
        <aside class="card ingredients">
            <h2>Ingredients</h2>
            <ul>
                <?php foreach ($ingredients as $item): ?>
                    <li><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <section class="card steps">
            <h2>Steps</h2>
            <ol>
                <?php foreach ($steps as $step): ?>
                    <li><?= e($step) ?></li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>
</article>

<section id="comments" class="comments">
    <h2>Comments (<?= count($commentList) ?>)</h2>

    <?php if (count($commentList) === 0): ?>
        <p class="empty">No comments yet. Tried this recipe? Tell the cook how it went.</p>
    <?php endif; ?>

    <?php foreach ($commentList as $comment): ?>
        <div class="card comment" id="comment-<?= e($comment['id']) ?>">
            <p class="meta">
                <strong><?= e($comment['author']) ?></strong>
                · <span class="stars" aria-label="<?= e($comment['rating']) ?> out of 5"><?= stars((float) $comment['rating']) ?></span>
                · <?= e(nice_date($comment['created_at'])) ?>
                <?php if ($comment['updated_at'] !== null): ?>
                    <span class="tag tag-edited" title="Edited <?= e(nice_date($comment['updated_at'])) ?>">Edited</span>
                <?php endif; ?>
            </p>

            <p><?= nl2br(e($comment['body'])) ?></p>

            <?php if ((int) $comment['user_id'] === $session->userId()): ?>
                <div class="actions">
                    <a href="comment-edit.php?id=<?= e($comment['id']) ?>">Edit</a>
                    <form method="post" action="comment-delete.php" class="inline" data-confirm="Delete this comment?">
                        <input type="hidden" name="id" value="<?= e($comment['id']) ?>">
                        <button type="submit" class="link-button danger-text">Delete</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <h3>Leave a comment</h3>
    <?php require 'partials/errors.php'; ?>

    <form method="post" class="card" action="recipe.php?id=<?= e($recipe['id']) ?>#comments">
        <label>Your rating
            <select name="rating" required>
                <option value="">Choose a rating…</option>
                <?php foreach ($ratingLabels as $value => $label): ?>
                    <option value="<?= $value ?>" <?= $commentForm['rating'] === (string) $value ? 'selected' : '' ?>>
                        <?= stars((float) $value) ?> <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Comment
            <textarea name="body" rows="3" minlength="2" maxlength="1000" required
                      placeholder="How did it turn out? Any tips?"><?= e($commentForm['body']) ?></textarea>
        </label>

        <button type="submit" class="button">Post comment</button>
    </form>
</section>

<?php require 'partials/footer.php'; ?>
