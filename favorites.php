<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

$list = (new Recipe($pdo))->favoritesOf($session->userId());
$removeOnUnsave = true; // the card disappears when un-saved here

$title = 'Favorites';
require 'partials/header.php';
?>

<section class="page-head">
    <div>
        <h1>My favorites</h1>
        <p class="lead">Recipes you saved to cook later.</p>
    </div>
</section>

<div class="empty" id="favorites-empty" <?= count($list) > 0 ? 'hidden' : '' ?>>
    <p>You haven't saved any recipes yet. Tap <strong>♥ Save</strong> on a recipe you like.</p>
    <p><a class="button" href="index.php">Browse recipes</a></p>
</div>

<div class="recipe-grid">
    <?php foreach ($list as $recipe): ?>
        <?php require 'partials/recipe-card.php'; ?>
    <?php endforeach; ?>
</div>

<?php require 'partials/footer.php'; ?>
