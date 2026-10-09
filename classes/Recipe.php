<?php
declare(strict_types=1);

class Recipe extends Repository
{
    // Feed: newest first, optional keyword and category filter.
    // The keyword is matched against the title, description, and ingredients.
    public function search(int $viewerId, string $keyword, int $categoryId): array
    {
        // Start with the shared recipe-card query and a condition that is always true.
        // The true condition lets us safely append more filters with "AND" below.
        $sql = $this->listQuery() . ' WHERE 1 = 1';

        // The viewer ID is used by listQuery() to determine which recipes are saved.
        $params = ['viewer' => $viewerId];

        if ($keyword !== '') {
            // Escape LIKE wildcard characters so a typed % or _ is searched literally.
            $like = '%' . addcslashes($keyword, '%_\\') . '%';

            // Search the recipe title, description, or any matching ingredient.
            // Separate placeholders are used because some database drivers do not
            // allow the same named placeholder to appear more than once.
            $sql .= ' AND (r.title LIKE :kw1
                       OR r.description LIKE :kw2
                       OR EXISTS (SELECT 1 FROM ingredients i
                                  WHERE i.recipe_id = r.id AND i.item LIKE :kw3))';

            // Bind the same search value to each part of the search condition.
            $params['kw1'] = $like;
            $params['kw2'] = $like;
            $params['kw3'] = $like;
        }

        if ($categoryId > 0) {
            // A positive category ID means the user selected a category filter.
            $sql .= ' AND r.category_id = :category';
            $params['category'] = $categoryId;
        }

        // Show the newest recipes first; the ID makes the order consistent
        // when two recipes have the same creation time.
        $sql .= ' ORDER BY r.created_at DESC, r.id DESC';

        // Prepare the SQL, bind all values safely, and fetch every matching row.
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // The member's saved recipes, most recently saved first.
    public function favoritesOf(int $userId): array
    {
        $sql = $this->listQuery() . '
            INNER JOIN favorites fav ON fav.recipe_id = r.id AND fav.user_id = :owner
            ORDER BY fav.created_at DESC, fav.id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['viewer' => $userId, 'owner' => $userId]);
        return $stmt->fetchAll();
    }

    // One recipe with everything shown on its page.
    public function find(int $id, int $viewerId): ?array
    {
        $sql = 'SELECT r.*, u.name AS author, c.name AS category,
                       (SELECT ROUND(AVG(cm.rating), 1) FROM comments cm WHERE cm.recipe_id = r.id) AS avg_rating,
                       (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id) AS comment_count,
                       EXISTS (SELECT 1 FROM favorites f
                               WHERE f.recipe_id = r.id AND f.user_id = :viewer) AS is_favorite
                FROM recipes r
                INNER JOIN users u ON u.id = r.user_id
                INNER JOIN categories c ON c.id = r.category_id
                WHERE r.id = :id';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id, 'viewer' => $viewerId]);
        $recipe = $stmt->fetch();

        return $recipe === false ? null : $recipe;
    }

    // Only returns the recipe if it belongs to this user (used before editing).
    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM recipes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $recipe = $stmt->fetch();

        return $recipe === false ? null : $recipe;
    }

    public function exists(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM recipes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() !== false;
    }

    // Ingredient lines in the order the member typed them.
    public function ingredients(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT item FROM ingredients WHERE recipe_id = :recipe_id ORDER BY sort_order, id'
        );
        $stmt->execute(['recipe_id' => $recipeId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // CREATE: the recipe row + one row per ingredient.
    // Transaction: all of it is saved, or none of it (Week 7, slide 12).
    public function create(int $userId, array $data): int
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                'INSERT INTO recipes (user_id, category_id, title, description, steps)
                 VALUES (:user_id, :category_id, :title, :description, :steps)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'category_id' => (int) $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'steps' => $data['steps'],
            ]);

            $recipeId = (int) $this->pdo->lastInsertId();
            $this->insertIngredients($recipeId, $data['ingredients']);

            $this->pdo->commit();
            return $recipeId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // UPDATE: change the recipe, replace its ingredient list, mark it as edited.
    // Returns false if the recipe is not this user's.
    public function update(int $id, int $userId, array $data): bool
    {
        try {
            $this->pdo->beginTransaction();

            // Ownership check + lock the row until commit
            $stmt = $this->pdo->prepare(
                'SELECT id FROM recipes WHERE id = :id AND user_id = :user_id FOR UPDATE'
            );
            $stmt->execute(['id' => $id, 'user_id' => $userId]);
            if ($stmt->fetch() === false) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare(
                'UPDATE recipes
                 SET category_id = :category_id, title = :title, description = :description,
                     steps = :steps, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'category_id' => (int) $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'],
                'steps' => $data['steps'],
                'id' => $id,
            ]);

            // Simplest way to save an edited list: remove the old rows, insert the new ones
            $stmt = $this->pdo->prepare('DELETE FROM ingredients WHERE recipe_id = :recipe_id');
            $stmt->execute(['recipe_id' => $id]);
            $this->insertIngredients($id, $data['ingredients']);

            $this->pdo->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // DELETE: ON DELETE CASCADE also removes its ingredients, comments and favorites.
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM recipes WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    private function insertIngredients(int $recipeId, array $items): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ingredients (recipe_id, item, sort_order)
             VALUES (:recipe_id, :item, :sort_order)'
        );

        foreach (array_values($items) as $index => $item) {
            $stmt->execute([
                'recipe_id' => $recipeId,
                'item' => $item,
                'sort_order' => $index + 1,
            ]);
        }
    }

    // Shared SELECT for recipe cards (feed and favorites page).
    private function listQuery(): string
    {
        return 'SELECT r.id, r.user_id, r.title, r.description, r.created_at, r.updated_at,
                       u.name AS author, c.name AS category,
                       (SELECT ROUND(AVG(cm.rating), 1) FROM comments cm WHERE cm.recipe_id = r.id) AS avg_rating,
                       (SELECT COUNT(*) FROM comments cm WHERE cm.recipe_id = r.id) AS comment_count,
                       EXISTS (SELECT 1 FROM favorites f
                               WHERE f.recipe_id = r.id AND f.user_id = :viewer) AS is_favorite
                FROM recipes r
                INNER JOIN users u ON u.id = r.user_id
                INNER JOIN categories c ON c.id = r.category_id';
    }
}
