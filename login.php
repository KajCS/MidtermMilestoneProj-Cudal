<?php
declare(strict_types=1);
require 'init.php';
$session->requireGuest();

$users = new User($pdo);
$validator = new Validator();
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = post('email');
    $password = post('password', false);

    $errors = $validator->validateLogin(['email' => $email, 'password' => $password]);

    if (!$errors) {
        $user = $users->verifyLogin($email, $password);

        if ($user !== null) {
            $session->login($user);
            header('Location: index.php');
            exit;
        }

        // Same message for a wrong email or a wrong password
        $errors[] = 'Incorrect email or password.';
    }
}

$title = 'Log in';
require 'partials/header.php';
?>

<section class="auth-card">
    <h1>Welcome back</h1>
    <p class="lead">Log in to see what your neighbors are cooking.</p>

    <?php require 'partials/errors.php'; ?>

    <form method="post" class="card">
        <label>Email
            <input type="email" name="email" value="<?= e($email) ?>" maxlength="255" autocomplete="email" required>
        </label>

        <label>Password
            <input type="password" name="password" autocomplete="current-password" required>
        </label>

        <button type="submit" class="button">Log in</button>
        <p class="hint">No account yet? <a href="register.php">Register</a></p>
    </form>
</section>

<?php require 'partials/footer.php'; ?>
