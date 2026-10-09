<?php
declare(strict_types=1);
require 'init.php';
$session->requireGuest();

$users = new User($pdo);
$validator = new Validator();
$errors = [];
$form = ['name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = [
        'name' => post('name'),
        'email' => post('email'),
        'password' => post('password', false),         // passwords are not trimmed
        'confirm_password' => post('confirm_password', false),
    ];

    $errors = $validator->validateRegister($form);

    if (!$errors && $users->emailExists($form['email'])) {
        $errors[] = 'That email is already registered.';
    }

    if (!$errors) {
        $users->create($form['name'], $form['email'], $form['password']);
        $session->flash('Account created. You can log in now.');
        header('Location: login.php');
        exit;
    }
}

$title = 'Register';
require 'partials/header.php';
?>

<section class="auth-card">
    <h1>Join the kusina</h1>
    <p class="lead">Create an account to share and save recipes.</p>

    <?php require 'partials/errors.php'; ?>

    <form method="post" class="card" data-register>
        <label>Name
            <input type="text" name="name" value="<?= e($form['name']) ?>"
                   minlength="2" maxlength="100" autocomplete="name" required>
        </label>

        <label>Email
            <input type="email" name="email" value="<?= e($form['email']) ?>"
                   maxlength="255" autocomplete="email" required>
        </label>

        <label>Password
            <input type="password" name="password" minlength="8" maxlength="72"
                   pattern="(?=.*[A-Za-z])(?=.*\d).+"
                   title="At least 8 characters, with a letter and a number"
                   autocomplete="new-password" required>
            <small>At least 8 characters, with a letter and a number.</small>
        </label>

        <label>Confirm password
            <input type="password" name="confirm_password" minlength="8" maxlength="72"
                   autocomplete="new-password" required>
        </label>

        <button type="submit" class="button">Create account</button>
        <p class="hint">Already a member? <a href="login.php">Log in</a></p>
    </form>
</section>

<?php require 'partials/footer.php'; ?>
