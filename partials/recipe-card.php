<?php
// Needs: $recipe. Optional: $removeOnUnsave (favorites page).
$saved = (bool) $recipe['is_favorite'];
?>
<article class="recipe-card">
    <div class="card-top">
        <span class="tag"><?= e($recipe['category']) ?></span>
        <button type="button"
                class="fav-btn"
                data-recipe-id="<?= e($recipe['id']) ?>"
                aria-pressed="<?= $saved ? 'true' : 'false' ?>"
                <?= !empty($removeOnUnsave) ? 'data-remove-card="1"' : '' ?>>
            <span class="fav-icon" aria-hidden="true">♥</span>
            <span class="fav-label"><?= $saved ? 'Saved' : 'Save' ?></span>
        </button>
    </div>

    <h2><a href="recipe.php?id=<?= e($recipe['id']) ?>"><?= e($recipe['title']) ?></a></h2>
    <p class="description"><?= e($recipe['description']) ?></p>

    <p class="meta">
        By <?= e($recipe['author']) ?> · <?= e(nice_date($recipe['created_at'])) ?>
        <?php if ($recipe['updated_at'] !== null): ?>
            <span class="tag tag-edited" title="Edited <?= e(nice_date($recipe['updated_at'])) ?>">Edited</span>
        <?php endif; ?>
    </p>

    <p class="rating-line">
        <?php if ($recipe['avg_rating'] !== null): ?>
            <span class="stars" aria-label="<?= e($recipe['avg_rating']) ?> out of 5"><?= stars((float) $recipe['avg_rating']) ?></span>
            <?= e($recipe['avg_rating']) ?>
        <?php else: ?>
            No ratings yet
        <?php endif; ?>
        · <?= e($recipe['comment_count']) ?> comment<?= (int) $recipe['comment_count'] === 1 ? '' : 's' ?>
    </p>
</article>
