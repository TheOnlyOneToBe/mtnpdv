<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\Produit;
use App\Domain\Repository\FluxProduitRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FluxProduit>
 */
class FluxProduitRepository extends ServiceEntityRepository implements FluxProduitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FluxProduit::class);
    }

    public function find(mixed $id, mixed $lockMode = null, mixed $lockVersion = null): ?FluxProduit
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /** @return list<FluxProduit> */
    public function findByFlux(FluxRavitaillement $flux): array
    {
        return $this->createQueryBuilder('fp')
            ->andWhere('fp.fluxRavitaillement = :flux')
            ->setParameter('flux', $flux)
            ->getQuery()
            ->getResult();
    }

    public function quantiteTotalePourProduit(Produit $produit): int
    {
        return (int) $this->createQueryBuilder('fp')
            ->select('COALESCE(SUM(fp.quantite), 0)')
            ->andWhere('fp.produit = :produit')
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<array{produitId: int, nomProd: string, quantiteTotale: int}> */
    public function quantitesTotalesParProduit(): array
    {
        $lignes = $this->createQueryBuilder('fp')
            ->select('IDENTITY(fp.produit) AS produitId, p.nomProd AS nomProd, SUM(fp.quantite) AS quantiteTotale')
            ->innerJoin('fp.produit', 'p')
            ->groupBy('fp.produit, p.nomProd')
            ->orderBy('quantiteTotale', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $ligne): array => [
                'produitId' => (int) $ligne['produitId'],
                'nomProd' => (string) $ligne['nomProd'],
                'quantiteTotale' => (int) $ligne['quantiteTotale'],
            ],
            $lignes,
        );
    }

    public function save(FluxProduit $ligne, bool $flush = true): void
    {
        $this->getEntityManager()->persist($ligne);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(FluxProduit $ligne, bool $flush = true): void
    {
        $this->getEntityManager()->remove($ligne);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
