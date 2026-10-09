<?php
declare(strict_types=1);

// Parent for every class that talks to the database.
// abstract: you never write "new Repository" (Week 6, slide 8).
// Each child HAS a PDO connection passed in through the constructor (composition, slide 11).
// protected: children can use $this->pdo, outside code cannot (slide 5).
abstract class Repository
{
    public function __construct(protected PDO $pdo)
    {
    }
}
