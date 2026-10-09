<?php
declare(strict_types=1);

// Only checks input rules. Never touches the database or $_SESSION (Week 6, slide 14).
// Public methods = what pages call. Private methods = hidden helpers (Week 6, slide 6).
class Validator
{
    public function validateRegister(array $data): array
    {
        $errors = [];

        if (!$this->lengthBetween($data['name'], 2, 100)) {
            $errors[] = 'Name must be 2 to 100 characters.';
        }
        if (!$this->isEmail($data['email'])) {
            $errors[] = 'Enter a valid email address.';
        }
        if (!$this->lengthBetween($data['password'], 8, 72)) {
            $errors[] = 'Password must be 8 to 72 characters.';
        } elseif (!$this->hasLetterAndNumber($data['password'])) {
            $errors[] = 'Password must contain at least one letter and one number.';
        } elseif ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }

        return $errors;
    }

    public function validateLogin(array $data): array
    {
        $errors = [];

        if (!$this->isEmail($data['email'])) {
            $errors[] = 'Enter a valid email address.';
        }
        if ($data['password'] === '') {
            $errors[] = 'Password is required.';
        }

        return $errors;
    }

    // $categoryIds = the ids that really exist in the categories table
    public function validateRecipe(array $data, array $categoryIds): array
    {
        $errors = [];

        if (!$this->lengthBetween($data['title'], 3, 150)) {
            $errors[] = 'Title must be 3 to 150 characters.';
        }
        if (!$this->lengthBetween($data['description'], 10, 255)) {
            $errors[] = 'Short description must be 10 to 255 characters.';
        }
        if (!in_array((int) $data['category_id'], $categoryIds, true)) {
            $errors[] = 'Choose a category from the list.';
        }

        $count = count($data['ingredients']);
        if ($count < 1) {
            $errors[] = 'Add at least one ingredient.';
        } elseif ($count > 30) {
            $errors[] = 'A recipe can have up to 30 ingredients.';
        }
        foreach ($data['ingredients'] as $item) {
            if (mb_strlen($item) > 150) {
                $errors[] = 'Each ingredient must be 150 characters or less.';
                break;
            }
        }

        if (!$this->lengthBetween($data['steps'], 20, 10000)) {
            $errors[] = 'Cooking steps must be 20 to 10,000 characters.';
        }

        return $errors;
    }

    public function validateComment(array $data): array
    {
        $errors = [];

        if (!$this->lengthBetween($data['body'], 2, 1000)) {
            $errors[] = 'Comment must be 2 to 1,000 characters.';
        }
        if (!$this->isIntBetween($data['rating'], 1, 5)) {
            $errors[] = 'Choose a rating from 1 to 5 stars.';
        }

        return $errors;
    }

    private function lengthBetween(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    private function isEmail(string $value): bool
    {
        return mb_strlen($value) <= 255 && filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function hasLetterAndNumber(string $value): bool
    {
        return preg_match('/[A-Za-z]/', $value) === 1 && preg_match('/\d/', $value) === 1;
    }

    private function isIntBetween(string $value, int $min, int $max): bool
    {
        $options = ['options' => ['min_range' => $min, 'max_range' => $max]];
        return filter_var($value, FILTER_VALIDATE_INT, $options) !== false;
    }
}
