<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\CategorieProd;

interface CategorieProdRepositoryInterface
{
    public function find(int $id): ?CategorieProd;

    /** @return list<CategorieProd> */
    public function findAll(): array;

    /** @return list<CategorieProd> */
    public function findByType(string $typeCat): array;

    public function save(CategorieProd $categorie, bool $flush = true): void;

    public function remove(CategorieProd $categorie, bool $flush = true): void;
}
