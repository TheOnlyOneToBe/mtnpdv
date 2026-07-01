<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\CategoriePdv;

interface CategoriePdvRepositoryInterface
{
    public function find(int $id): ?CategoriePdv;

    /** @return list<CategoriePdv> */
    public function findAll(): array;

    public function findOneByLibelle(string $libelle): ?CategoriePdv;

    public function save(CategoriePdv $categorie, bool $flush = true): void;

    public function remove(CategoriePdv $categorie, bool $flush = true): void;
}
