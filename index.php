<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

$recipes = new Recipe($pdo);
$categories = (new Category($pdo))->all();

// Search and filter come from the URL (GET), e.g. index.php?q=adobo&category=1
$keyword = trim((string) ($_GET['q'] ?? ''));
$keyword = mb_substr($keyword, 0, 100);
$categoryId = (int) ($_GET['category'] ?? 0);

$list = $recipes->search($session->userId(), $keyword, $categoryId);
$isFiltered = $keyword !== '' || $categoryId > 0;

$title = 'Recipes';
require 'partials/header.php';
?>

<section class="page-head">
    <div>
        <h1>Ano'ng ulam?</h1>
        <p class="lead">The latest recipes from home cooks in the barangay.</p>
    </div>
    <a class="button" href="recipe-form.php">Share a recipe</a>
</section>

<form method="get" action="index.php" class="filter-bar" role="search">
    <label class="sr-only" for="q">Search recipes</label>
    <input type="search" id="q" name="q" value="<?= e($keyword) ?>" maxlength="100"
           placeholder="Search by name or ingredient, e.g. gata">

    <label class="sr-only" for="category">Category</label>
    <select id="category" name="category">
        <option value="0">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['id']) ?>" <?= (int) $category['id'] === $categoryId ? 'selected' : '' ?>>
                <?= e($category['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="button">Search</button>
    <?php if ($isFiltered): ?>
        <a class="button outline" href="index.php">Clear</a>
    <?php endif; ?>
</form>

<?php if ($isFiltered): ?>
    <p class="result-count"><?= count($list) ?> recipe<?= count($list) === 1 ? '' : 's' ?> found</p>
<?php endif; ?>

<?php if (count($list) === 0): ?>
    <div class="empty">
        <?php if ($isFiltered): ?>
            <p>No recipes match your search. Try another word or category.</p>
        <?php else: ?>
            <p>No recipes yet. Be the first to share one!</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="recipe-grid">
        <?php foreach ($list as $recipe): ?>
            <?php require 'partials/recipe-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
