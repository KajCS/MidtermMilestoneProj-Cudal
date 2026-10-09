<?php
declare(strict_types=1);
require 'init.php';
$session->requireLogin();

$recipes = new Recipe($pdo);
$validator = new Validator();
$categories = (new Category($pdo))->all();
$categoryIds = array_map(fn (array $c): int => (int) $c['id'], $categories);

// recipe-form.php        -> share a new recipe
// recipe-form.php?id=5   -> edit recipe 5 (only if it is yours)
$id = (int) ($_GET['id'] ?? 0);
$isEdit = $id > 0;
$errors = [];

$form = [
    'title' => '',
    'description' => '',
    'category_id' => '',
    'steps' => '',
    'ingredients' => [''],
];

if ($isEdit) {
    $existing = $recipes->findOwned($id, $session->userId());
    if ($existing === null) {
        http_response_code(404);
        exit('Recipe not found, or you are not allowed to edit it.');
    }

    $form = [
        'title' => $existing['title'],
        'description' => $existing['description'],
        'category_id' => (string) $existing['category_id'],
        'steps' => $existing['steps'],
        'ingredients' => $recipes->ingredients($id) ?: [''],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ingredients[] arrives as an array; keep only non-empty text lines
    $ingredients = [];
    $rawIngredients = $_POST['ingredients'] ?? [];
    if (is_array($rawIngredients)) {
        foreach ($rawIngredients as $item) {
            if (is_string($item) && trim($item) !== '') {
                $ingredients[] = trim($item);
            }
        }
    }

    $form = [
        'title' => post('title'),
        'description' => post('description'),
        'category_id' => post('category_id'),
        'steps' => post('steps'),
        'ingredients' => $ingredients,
    ];

    $errors = $validator->validateRecipe($form, $categoryIds);

    if (!$errors) {
        if ($isEdit) {
            if (!$recipes->update($id, $session->userId(), $form)) {
                http_response_code(404);
                exit('Recipe not found, or you are not allowed to edit it.');
            }
            $session->flash('Recipe updated.');
        } else {
            $id = $recipes->create($session->userId(), $form);
            $session->flash('Your recipe is now shared with the barangay!');
        }

        header('Location: recipe.php?id=' . $id);
        exit;
    }

    if (count($form['ingredients']) === 0) {
        $form['ingredients'] = [''];
    }
}

$title = $isEdit ? 'Edit recipe' : 'Share a recipe';
require 'partials/header.php';
?>

<section class="form-page">
    <h1><?= e($title) ?></h1>
    <?php require 'partials/errors.php'; ?>

    <form method="post" class="card" id="recipe-form">
        <label>Title
            <input type="text" name="title" value="<?= e($form['title']) ?>"
                   minlength="3" maxlength="150" placeholder="e.g. Lola's Chicken Adobo" required>
        </label>

        <label>Short description
            <textarea name="description" rows="2" minlength="10" maxlength="255" required
                      placeholder="One or two sentences about this dish"><?= e($form['description']) ?></textarea>
        </label>

        <label>Category
            <select name="category_id" required>
                <option value="">Choose a category…</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e($category['id']) ?>" <?= (string) $category['id'] === $form['category_id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <fieldset>
            <legend>Ingredients <small>(one per box, up to 30)</small></legend>

            <div id="ingredient-list">
                <?php foreach ($form['ingredients'] as $item): ?>
                    <div class="ingredient-row">
                        <input type="text" name="ingredients[]" value="<?= e($item) ?>" maxlength="150"
                               placeholder="e.g. 4 cloves garlic, minced" aria-label="Ingredient" required>
                        <button type="button" class="remove-ingredient" aria-label="Remove this ingredient">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <button type="button" id="add-ingredient" class="button outline small">+ Add ingredient</button>
        </fieldset>

        <label>Cooking steps
            <textarea name="steps" rows="8" minlength="20" maxlength="10000" required
                      placeholder="Write one step per line"><?= e($form['steps']) ?></textarea>
            <small>Put each step on its own line; they will show as a numbered list.</small>
        </label>

        <div class="form-actions">
            <button type="submit" class="button"><?= $isEdit ? 'Save changes' : 'Share recipe' ?></button>
            <a class="button outline" href="<?= $isEdit ? 'recipe.php?id=' . $id : 'index.php' ?>">Cancel</a>
        </div>
    </form>
</section>

<?php require 'partials/footer.php'; ?>
