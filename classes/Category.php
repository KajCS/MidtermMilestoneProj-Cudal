<?php
declare(strict_types=1);

class Category extends Repository
{
    public function all(): array
    {
        return $this->pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
    }
}
