<?php
declare(strict_types=1);

class Favorite extends Repository
{
    public function add(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO favorites (user_id, recipe_id) VALUES (:user_id, :recipe_id)'
        );

        try {
            $stmt->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
        } catch (PDOException $e) {
            // 23000 = the UNIQUE (user_id, recipe_id) pair already exists: already saved, nothing to do
            if ($e->getCode() !== '23000') {
                throw $e;
            }
        }
    }

    public function remove(int $userId, int $recipeId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM favorites WHERE user_id = :user_id AND recipe_id = :recipe_id'
        );
        $stmt->execute(['user_id' => $userId, 'recipe_id' => $recipeId]);
    }
}
