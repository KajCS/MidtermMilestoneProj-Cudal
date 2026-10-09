<?php if (!empty($errors)): ?>
    <ul class="errors" role="alert">
        <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
