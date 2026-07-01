<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CategoriePdv;
use App\Domain\Repository\CategoriePdvRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategoriePdv>
 */
class CategoriePdvRepository extends ServiceEntityRepository implements CategoriePdvRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoriePdv::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?CategoriePdv
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByLibelle(string $libelle): ?CategoriePdv
    {
        return $this->findOneBy(['libelleCatpdv' => $libelle]);
    }

    public function save(CategoriePdv $categorie, bool $flush = true): void
    {
        $this->getEntityManager()->persist($categorie);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategoriePdv $categorie, bool $flush = true): void
    {
        $this->getEntityManager()->remove($categorie);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
