<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\Role;
use App\Domain\Repository\RoleRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Role>
 */
class RoleRepository extends ServiceEntityRepository implements RoleRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?Role
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByCode(string $codeRole): ?Role
    {
        return $this->findOneBy(['codeRole' => strtoupper($codeRole)]);
    }

    public function save(Role $role, bool $flush = true): void
    {
        $this->getEntityManager()->persist($role);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Role $role, bool $flush = true): void
    {
        $this->getEntityManager()->remove($role);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
