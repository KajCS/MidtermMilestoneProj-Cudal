<?php
declare(strict_types=1);

class Comment extends Repository
{
    public function forRecipe(int $recipeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cm.*, u.name AS author
             FROM comments cm
             INNER JOIN users u ON u.id = cm.user_id
             WHERE cm.recipe_id = :recipe_id
             ORDER BY cm.created_at ASC, cm.id ASC'
        );
        $stmt->execute(['recipe_id' => $recipeId]);
        return $stmt->fetchAll();
    }

    public function create(int $recipeId, int $userId, string $body, int $rating): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO comments (recipe_id, user_id, body, rating)
             VALUES (:recipe_id, :user_id, :body, :rating)'
        );
        $stmt->execute([
            'recipe_id' => $recipeId,
            'user_id' => $userId,
            'body' => $body,
            'rating' => $rating,
        ]);
    }

    // Only returns the comment if it belongs to this user.
    public function findOwned(int $id, int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM comments WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $comment = $stmt->fetch();

        return $comment === false ? null : $comment;
    }

    // The ownership check is inside the WHERE, so it is one safe query.
    // updated_at = NOW() shows the "Edited" tag.
    public function update(int $id, int $userId, string $body, int $rating): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE comments SET body = :body, rating = :rating, updated_at = NOW()
             WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'body' => $body,
            'rating' => $rating,
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }
}
