<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> · Kusina ng Barangay</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="wrap header-inner">
            <a class="brand" href="index.php">
                <span class="brand-mark" aria-hidden="true">L</span>
                <span>Kusina ni Lola</span>
            </a>

            <nav class="site-nav">
                <?php if ($session->isLoggedIn()): ?>
                    <a href="index.php">Recipes</a>
                    <a href="recipe-form.php">Share a recipe</a>
                    <a href="favorites.php">Favorites</a>
                    <form method="post" action="logout.php" class="inline">
                        <button type="submit" class="link-button">Log out (<?= e($session->userName()) ?>)</button>
                    </form>
                <?php else: ?>
                    <a href="login.php">Log in</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="wrap">
        <?php $flash = $session->takeFlash(); ?>
        <?php if ($flash !== null): ?>
            <p class="notice"><?= e($flash) ?></p>
        <?php endif; ?>
