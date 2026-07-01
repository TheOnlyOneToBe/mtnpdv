<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\Produit;
use App\Domain\Enum\StatutProduit;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\ValueObject\Montant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository implements ProduitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?Produit
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findOneByCodeBarre(string $codeBarre): ?Produit
    {
        return $this->findOneBy(['codeBarre' => $codeBarre]);
    }

    /** @return list<Produit> */
    public function findActifs(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.statutProd = :statut')
            ->setParameter('statut', StatutProduit::ACTIF)
            ->orderBy('p.nomProd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Produit> */
    public function findByCategorie(CategorieProd $categorie): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.categorie = :categorie')
            ->setParameter('categorie', $categorie)
            ->orderBy('p.nomProd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Produit> */
    public function rechercherParNom(string $terme): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('LOWER(p.nomProd) LIKE :terme')
            ->setParameter('terme', '%'.mb_strtolower($terme).'%')
            ->orderBy('p.nomProd', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Produit> */
    public function findDansFourchettePrix(Montant $min, Montant $max): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.prixUnitaire BETWEEN :min AND :max')
            ->setParameter('min', $min->toDecimal())
            ->setParameter('max', $max->toDecimal())
            ->orderBy('p.prixUnitaire', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Produit $produit, bool $flush = true): void
    {
        $this->getEntityManager()->persist($produit);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Produit $produit, bool $flush = true): void
    {
        $this->getEntityManager()->remove($produit);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
