<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Role;

interface RoleRepositoryInterface
{
    public function find(int $id): ?Role;

    /** @return list<Role> */
    public function findAll(): array;

    public function findOneByCode(string $codeRole): ?Role;

    public function save(Role $role, bool $flush = true): void;

    public function remove(Role $role, bool $flush = true): void;
}
