<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CategorieProd;
use App\Domain\Repository\CategorieProdRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CategorieProd>
 */
class CategorieProdRepository extends ServiceEntityRepository implements CategorieProdRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategorieProd::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?CategorieProd
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /** @return list<CategorieProd> */
    public function findByType(string $typeCat): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.typeCat = :type')
            ->setParameter('type', $typeCat)
            ->orderBy('c.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(CategorieProd $categorie, bool $flush = true): void
    {
        $this->getEntityManager()->persist($categorie);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CategorieProd $categorie, bool $flush = true): void
    {
        $this->getEntityManager()->remove($categorie);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
