<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\Enum\StatutFlux;
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

    /** @return list<array{produit: Produit, quantiteLivree: int}> */
    public function findLivresAuPointVente(PointVente $pointVente): array
    {
        $lignes = $this->getEntityManager()->createQueryBuilder()
            ->select('p AS produit, SUM(fp.quantite) AS quantiteLivree')
            ->from(Produit::class, 'p')
            ->innerJoin(FluxProduit::class, 'fp', 'WITH', 'fp.produit = p')
            ->innerJoin('fp.fluxRavitaillement', 'f')
            ->andWhere('f.pointVente = :pdv')
            ->andWhere('f.statutFlux = :statut')
            ->setParameter('pdv', $pointVente)
            ->setParameter('statut', StatutFlux::LIVRE)
            ->groupBy('p.id')
            ->orderBy('p.nomProd', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(
            static fn (array $ligne): array => [
                'produit' => $ligne['produit'],
                'quantiteLivree' => (int) $ligne['quantiteLivree'],
            ],
            $lignes,
        );
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
